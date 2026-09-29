const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
} = require('@whiskeysockets/baileys');
const { Boom } = require('@hapi/boom');
const qrcodeTerminal = require('qrcode-terminal');
const QRCode = require('qrcode');
const pino = require('pino');
const express = require('express');
const cors = require('cors');
const fs = require('fs-extra');
const path = require('path');

const app = express();
const port = process.env.PORT || 5001;

app.use(cors());
app.use(express.json());

let sock = null;
let currentQrCode = null;
let connectionState = 'DISCONNECTED'; // 'DISCONNECTED' | 'PAIRING' | 'CONNECTED'
let connectedUser = null;
const AUTH_DIR = path.join(__dirname, 'auth_info_baileys');

async function connectToWhatsApp() {
    try {
        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version, isLatest } = await fetchLatestBaileysVersion();
        console.log(`[WA-GATEWAY] Using WhatsApp Web v${version.join('.')}, isLatest: ${isLatest}`);

        sock = makeWASocket({
            version,
            logger: pino({ level: 'silent' }),
            printQRInTerminal: false,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
            },
        });

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                connectionState = 'PAIRING';
                console.log('[WA-GATEWAY] New QR code generated. Waiting for scan...');
                qrcodeTerminal.generate(qr, { small: true });
                try {
                    currentQrCode = await QRCode.toDataURL(qr);
                } catch (err) {
                    console.error('[WA-GATEWAY] Error generating QR DataURL:', err);
                }
            }

            if (connection === 'open') {
                connectionState = 'CONNECTED';
                currentQrCode = null;
                connectedUser = sock?.user || null;
                const rawPhone = connectedUser?.id ? connectedUser.id.split(':')[0].split('@')[0] : 'Unknown';
                console.log(`[WA-GATEWAY] Connected successfully to WhatsApp! Logged in as: +${rawPhone}`);
            }

            if (connection === 'close') {
                const statusCode = (lastDisconnect?.error instanceof Boom) 
                    ? lastDisconnect.error.output?.statusCode 
                    : null;
                
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                console.log(`[WA-GATEWAY] Connection closed (Status Code: ${statusCode}). Reconnecting: ${shouldReconnect}`);

                if (statusCode === DisconnectReason.loggedOut) {
                    connectionState = 'DISCONNECTED';
                    currentQrCode = null;
                    connectedUser = null;
                    await fs.remove(AUTH_DIR);
                    console.log('[WA-GATEWAY] Logged out. Session cleared.');
                    setTimeout(connectToWhatsApp, 2000);
                } else if (shouldReconnect) {
                    connectionState = 'PAIRING';
                    setTimeout(connectToWhatsApp, 3000);
                } else {
                    connectionState = 'DISCONNECTED';
                }
            }
        });

        sock.ev.on('creds.update', saveCreds);

    } catch (error) {
        console.error('[WA-GATEWAY] Error in connectToWhatsApp:', error);
        connectionState = 'DISCONNECTED';
    }
}

// REST API Endpoints

// 1. Get Connection & Session Status
app.get('/status', (req, res) => {
    const rawId = connectedUser?.id || '';
    const formattedPhone = rawId ? rawId.split(':')[0].split('@')[0] : null;

    res.json({
        status: connectionState,
        connected: connectionState === 'CONNECTED',
        user: connectedUser ? {
            id: connectedUser.id,
            name: connectedUser.name || 'M-PAD Admin WA',
            phone: formattedPhone ? `+${formattedPhone}` : null
        } : null,
        qr: currentQrCode,
        timestamp: new Date().toISOString()
    });
});

// 2. Get QR Code Data URL directly
app.get('/qr', (req, res) => {
    res.json({
        status: connectionState,
        qr: currentQrCode
    });
});

// Helper: resolve spintax if incoming message contains variations
function parseSpintax(text) {
    if (!text || typeof text !== 'string') return text;
    while (/\{([^{}]+)\}/.test(text)) {
        text = text.replace(/\{([^{}]+)\}/g, (_, group) => {
            const parts = group.split('|');
            return parts[Math.floor(Math.random() * parts.length)];
        });
    }
    return text;
}

// 3. Send Message Endpoint (with Anti-Ban Protocol: Typing Simulation & Jittering)
app.post('/send-message', async (req, res) => {
    const { number, phone, message, priority = 'high', skip_typing = false } = req.body;
    let targetNumber = number || phone;

    if (!targetNumber || !message) {
        return res.status(400).json({ 
            success: false, 
            error: 'Parameter number (or phone) and message are required' 
        });
    }

    if (connectionState !== 'CONNECTED' || !sock) {
        return res.status(503).json({ 
            success: false, 
            error: 'WhatsApp Gateway is not connected. Please scan QR code first.' 
        });
    }

    try {
        // Clean & format phone number
        let cleanPhone = targetNumber.replace(/[^0-9]/g, '');
        if (cleanPhone.startsWith('0')) {
            cleanPhone = '62' + cleanPhone.substring(1);
        }

        const whatsappJid = `${cleanPhone}@s.whatsapp.net`;
        const spunMessage = parseSpintax(message);

        // Anti-Ban Protocol 1: Jittering Algorithm
        // High: 500-1500ms (instant OTP) | Medium: 2000-4000ms | Low/Bulk: 5000-15000ms
        let jitterMs = 800;
        if (priority === 'low' || priority === 'bulk') {
            jitterMs = Math.floor(Math.random() * 10000) + 5000; // 5s to 15s
        } else if (priority === 'medium') {
            jitterMs = Math.floor(Math.random() * 2000) + 2000; // 2s to 4s
        } else {
            jitterMs = Math.floor(Math.random() * 1000) + 500; // 0.5s to 1.5s
        }
        await new Promise(resolve => setTimeout(resolve, jitterMs));

        // Anti-Ban Protocol 2: Typing Simulation (sendPresenceUpdate 'composing')
        if (!skip_typing) {
            try {
                await sock.sendPresenceUpdate('composing', whatsappJid);
                // 2 to 4 seconds typing simulation (or 1.5s for high priority OTP)
                const typingMs = priority === 'high' 
                    ? Math.floor(Math.random() * 1000) + 1500 
                    : Math.floor(Math.random() * 2000) + 2000;
                await new Promise(resolve => setTimeout(resolve, typingMs));
                await sock.sendPresenceUpdate('paused', whatsappJid);
            } catch (errPresence) {
                console.warn('[WA-GATEWAY] Typing presence warning:', errPresence.message);
            }
        }

        const result = await sock.sendMessage(whatsappJid, { text: spunMessage });

        console.log(`[WA-GATEWAY] Message sent to +${cleanPhone} (Priority: ${priority}, Jitter: ${jitterMs}ms)`);
        return res.json({ 
            success: true, 
            message: 'Message sent successfully',
            jid: whatsappJid,
            messageId: result?.key?.id,
            priority,
            jitterMs
        });
    } catch (err) {
        console.error('[WA-GATEWAY] Failed to send message:', err);
        return res.status(500).json({ 
            success: false, 
            error: err.message || 'Failed to send message' 
        });
    }
});

// 4. Logout Session Endpoint
app.post('/logout', async (req, res) => {
    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (e) {
                // Ignore logout errors if socket is already closed
            }
        }

        connectionState = 'DISCONNECTED';
        currentQrCode = null;
        connectedUser = null;

        await fs.remove(AUTH_DIR);
        console.log('[WA-GATEWAY] Session logged out by API request. Session files deleted.');

        // Restart connection after 1 second to generate new QR
        setTimeout(() => {
            connectToWhatsApp();
        }, 1000);

        return res.json({ 
            success: true, 
            message: 'Session disconnected and cleared. Generating new QR code...' 
        });
    } catch (err) {
        console.error('[WA-GATEWAY] Logout failed:', err);
        return res.status(500).json({ 
            success: false, 
            error: err.message || 'Failed to logout session' 
        });
    }
});

// Start Gateway Server
app.listen(port, () => {
    console.log(`=================================================`);
    console.log(`[WA-GATEWAY] Server active at http://localhost:${port}`);
    console.log(`=================================================`);
    connectToWhatsApp();
});
