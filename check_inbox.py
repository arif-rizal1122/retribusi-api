import imaplib
import email
from email.header import decode_header
import ssl

IMAP_SERVER = "smtp.baubaukota.go.id"
EMAIL_ACCOUNT = "bapenda@baubaukota.go.id"
PASSWORD = "4Znr2_3kL7"

def clean_text(text):
    if isinstance(text, bytes):
        try:
            return text.decode('utf-8', errors='ignore')
        except:
            return text.decode('latin-1', errors='ignore')
    return str(text)

def check_inbox():
    print(f"Menghubungkan ke {IMAP_SERVER}...")
    try:
        context = ssl.create_default_context()
        context.check_hostname = False
        context.verify_mode = ssl.CERT_NONE
        
        mail = imaplib.IMAP4_SSL(IMAP_SERVER, 993, ssl_context=context)
        mail.login(EMAIL_ACCOUNT, PASSWORD)
        
        status, messages = mail.select("INBOX")
        if status != 'OK':
            print("Gagal membuka Inbox.")
            return

        message_count = int(messages[0])
        print(f"Total pesan di Inbox: {message_count}\n")
        
        # Ambil 5 pesan terakhir
        start = max(1, message_count - 4)
        for i in range(message_count, start - 1, -1):
            res, msg_data = mail.fetch(str(i), "(RFC822)")
            for response_part in msg_data:
                if isinstance(response_part, tuple):
                    msg = email.message_from_bytes(response_part[1])
                    
                    # Dekode Subject
                    subject, encoding = decode_header(msg["Subject"])[0]
                    subject = clean_text(subject)
                    
                    # Dekode From
                    sender, encoding = decode_header(msg.get("From"))[0]
                    sender = clean_text(sender)
                    
                    date = msg.get("Date")
                    
                    print(f"[{i}] Dari: {sender}")
                    print(f"    Tanggal: {date}")
                    print(f"    Subjek: {subject}")
                    print("-" * 50)
                    
        mail.logout()
    except Exception as e:
        print("Terjadi kesalahan:", e)

if __name__ == "__main__":
    check_inbox()
