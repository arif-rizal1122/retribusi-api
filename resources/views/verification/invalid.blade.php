<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dokumen Tidak Ditemukan' }} - M-PAD Kota Baubau</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0F2547;
            background-image: radial-gradient(#1e3a8a 1px, transparent 1px);
            background-size: 24px 24px;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .v-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .v-header-invalid {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            padding: 2rem 1.5rem 1.75rem 1.5rem;
            text-align: center;
        }
        .icon-circle {
            width: 58px;
            height: 58px;
            background: rgba(255, 255, 255, 0.2);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem auto;
        }
        .icon-circle svg {
            width: 32px;
            height: 32px;
            max-width: 32px;
            max-height: 32px;
            display: block;
            color: #ffffff;
        }
        .v-instansi {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.25rem;
        }
        .v-title {
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .v-subtitle {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.35rem;
        }
        .v-body {
            padding: 1.75rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            text-align: center;
            background: #ffffff;
        }
        .v-card-inner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 1rem;
            padding: 1rem;
            text-align: left;
        }
        .v-label {
            font-size: 0.625rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #991b1b;
            margin-bottom: 0.25rem;
        }
        .v-val-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.95rem;
            font-weight: 800;
            color: #b91c1c;
            letter-spacing: 0.05em;
            word-break: break-all;
        }
        .v-desc {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.5;
        }
        .v-legal-notice {
            font-size: 0.65rem;
            line-height: 1.4;
            color: #94a3b8;
            font-style: italic;
        }
        .v-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .v-footer-icon {
            width: 20px;
            height: 20px;
            background: #64748b;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        .v-footer-icon svg {
            width: 12px;
            height: 12px;
            max-width: 12px;
            max-height: 12px;
        }
        .v-footer-text {
            font-size: 0.65rem;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="v-container">
        <!-- Header -->
        <div class="v-header-invalid">
            <div class="icon-circle">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="v-instansi">Pemerintah Kota Baubau</div>
            <h1 class="v-title">Dokumen Tidak Ditemukan</h1>
            <p class="v-subtitle">Sistem Verifikasi Publik V-TAX Engine</p>
        </div>

        <!-- Body -->
        <div class="v-body">
            @if(isset($number) && $number)
            <div class="v-card-inner">
                <div class="v-label">Nomor yang Dicari</div>
                <div class="v-val-code">{{ $number }}</div>
            </div>
            @endif

            <p class="v-desc">
                Maaf, data untuk nomor dokumen atau tanda bayar tersebut tidak terdaftar di basis data resmi Bapenda / M-PAD Kota Baubau.
                Pastikan Anda memindai QR Code dari lembar dokumen resmi asli.
            </p>

            <p class="v-legal-notice">
                * Hubungi kantor BAPENDA Kota Baubau atau OPD terkait jika Anda merasa dokumen ini sah.
            </p>
        </div>

        <!-- Footer -->
        <div class="v-footer">
            <div class="v-footer-icon">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <span class="v-footer-text">Protected by V-TAX Engine • Baubau</span>
        </div>
    </div>
</body>
</html>
