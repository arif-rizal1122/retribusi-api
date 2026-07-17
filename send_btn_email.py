import smtplib
from email.message import EmailMessage
import os

# Konfigurasi Kredensial Email (Dari PDF Srikandi)
SMTP_SERVER = "smtp.baubaukota.go.id"
SMTP_PORT = 465
SENDER_EMAIL = "bapenda@baubaukota.go.id"
SENDER_PASSWORD = "4Znr2_3kL7"

# Tujuan
RECIPIENTS = ["risna21064@btn.co.id", "ExtYosua@btn.co.id"]

# Data Lampiran
ZIP_FILE_PATH = "docs/10_payment_gateway_h2h/BTN/Kredensial_DEV_BTN.zip"
PDF_FILE_PATH = "docs/10_payment_gateway_h2h/srikandi_file_1783395948006.pdf"

def main():
    print("Mempersiapkan email...")
    
    msg = EmailMessage()
    msg['Subject'] = "[M-PAD Baubau] Kredensial, Public Key, dan URL Callback Sandboxing (DEV)"
    msg['From'] = SENDER_EMAIL
    msg['To'] = ", ".join(RECIPIENTS)

    body = """Selamat Malam Tim Bank BTN,

Melalui email ini, kami dari Tim Bapenda (M-PAD) Kota Baubau bermaksud mengirimkan kelengkapan data integrasi SNAP BI untuk environment Sandboxing (DEV) sesuai dengan permintaan pada grup WhatsApp.

Berikut kami lampirkan dokumen-dokumen terkait:
1. File Surat/Dokumen Pengantar Resmi (terlampir dalam format PDF).
2. Data Kredensial (terlampir dalam format ZIP), yang berisikan:
   - Public Key m-PAD (.pem)
   - Dokumen Kredensial Callback (Client ID & Client Secret)
   - Informasi URL Endpoint (Inquiry, Payment, Access Token, dan QRIS)

Apabila terdapat kendala saat uji coba atau ada informasi tambahan yang diperlukan, mohon jangan ragu untuk menghubungi kami.

Terima kasih atas kerja samanya.

Salam,
Tim Teknis M-PAD Baubau"""
    msg.set_content(body)

    # Attach ZIP
    if os.path.exists(ZIP_FILE_PATH):
        with open(ZIP_FILE_PATH, 'rb') as f:
            zip_data = f.read()
        msg.add_attachment(zip_data, maintype='application', subtype='zip', filename="Kredensial_DEV_BTN.zip")
        print("✅ Lampiran ZIP berhasil ditambahkan.")
    else:
        print("❌ File ZIP tidak ditemukan!")

    # Attach PDF
    if os.path.exists(PDF_FILE_PATH):
        with open(PDF_FILE_PATH, 'rb') as f:
            pdf_data = f.read()
        msg.add_attachment(pdf_data, maintype='application', subtype='pdf', filename="Pengantar_Kredensial_Bapenda.pdf")
        print("✅ Lampiran PDF berhasil ditambahkan.")
    else:
        print("❌ File PDF tidak ditemukan!")

    print(f"\nMenghubungkan ke {SMTP_SERVER}:{SMTP_PORT}...")
    try:
        with smtplib.SMTP_SSL(SMTP_SERVER, SMTP_PORT) as server:
            server.login(SENDER_EMAIL, SENDER_PASSWORD)
            server.send_message(msg)
            print("🚀 Email berhasil terkirim ke:", ", ".join(RECIPIENTS))
    except Exception as e:
        print("❌ Gagal mengirim email:", e)

if __name__ == "__main__":
    main()
