import os
from PIL import Image, ImageDraw, ImageFont

# Path konfigurasi
INPUT_DIR = "/Users/pondokit/Downloads/Form Internal Integrasi Layanan API BNI_v1.3_"
OUTPUT_DIR = "/Users/pondokit/Downloads/Form_Internal_BNI_Terisi"
FONT_PATH = "/System/Library/Fonts/Helvetica.ttc" # Font default mac

if not os.path.exists(OUTPUT_DIR):
    os.makedirs(OUTPUT_DIR)

try:
    font_text = ImageFont.truetype(FONT_PATH, 18)
    font_check = ImageFont.truetype(FONT_PATH, 24)
except IOError:
    font_text = ImageFont.load_default()
    font_check = ImageFont.load_default()

def draw_on_page(page_num, commands):
    filename = f"Form Internal Integrasi Layanan API BNI_v1.3__page-000{page_num}.jpg"
    img_path = os.path.join(INPUT_DIR, filename)
    if not os.path.exists(img_path):
        print(f"File {img_path} tidak ditemukan.")
        return
    
    img = Image.open(img_path)
    draw = ImageDraw.Draw(img)
    
    for cmd in commands:
        x, y = cmd['x'], cmd['y']
        if cmd['type'] == 'text':
            draw.text((x, y), cmd['text'], fill="black", font=font_text)
        elif cmd['type'] == 'check':
            # Gambar centang (V) tebal
            draw.text((x, y), "V", fill="black", font=font_check)
            
    out_path = os.path.join(OUTPUT_DIR, filename.replace(".jpg", "_terisi.jpg"))
    img.save(out_path)
    print(f"Berhasil menyimpan {out_path}")

# PERKIRAAN KOORDINAT (X, Y) PADA GAMBAR 1125x1500
# Anda bisa mengubah angka X dan Y di bawah ini jika teks/centang meleset dari kotaknya.

# HALAMAN 1
page1 = [
    # Centang Pendaftaran Layanan
    {'type': 'check', 'x': 45, 'y': 150},
    
    # Nama Perusahaan & Alias
    {'type': 'text', 'text': 'Badan Pendapatan Daerah (Bapenda) Kota Baubau', 'x': 270, 'y': 252},
    {'type': 'text', 'text': 'M-PAD (Smart Revenue System)', 'x': 270, 'y': 282},
    
    # Bukan Teknologi Finansial
    {'type': 'check', 'x': 275, 'y': 622},
    {'type': 'text', 'text': 'Instansi Pemerintah Daerah - Bapenda Kota Baubau', 'x': 450, 'y': 642},
    
    # Tujuan Penggunaan
    {'type': 'text', 'text': 'Integrasi penerimaan Pajak/Retribusi Daerah (H2H) via Virtual Account & QRIS Dinamis ke M-PAD', 'x': 270, 'y': 662},
    
    # Nomor Aplikasi
    {'type': 'text', 'text': 'M-PAD', 'x': 320, 'y': 730},
    
    # Contact Person 1
    {'type': 'text', 'text': 'Muhdan Fyan Syah Sofian', 'x': 100, 'y': 825},
    {'type': 'text', 'text': 'Project Manager M-PAD', 'x': 370, 'y': 825},
    {'type': 'text', 'text': '08123456789', 'x': 540, 'y': 825},
    {'type': 'text', 'text': 'fyan@baubaukota.go.id', 'x': 730, 'y': 825},
]

# HALAMAN 2
page2 = [
    # Data Nasabah PIC
    {'type': 'text', 'text': 'Muhdan Fyan Syah Sofian', 'x': 320, 'y': 125},
    {'type': 'text', 'text': 'fyan@baubaukota.go.id', 'x': 540, 'y': 125},
    {'type': 'text', 'text': '08123456789', 'x': 840, 'y': 125},
    
    {'type': 'text', 'text': 'Nama Keamanan/Server', 'x': 320, 'y': 185},
    {'type': 'text', 'text': 'admin@baubaukota.go.id', 'x': 540, 'y': 185},
    {'type': 'text', 'text': '08123456789', 'x': 840, 'y': 185},
    
    {'type': 'text', 'text': 'Nama Developer M-PAD', 'x': 320, 'y': 245},
    {'type': 'text', 'text': 'dev@baubaukota.go.id', 'x': 540, 'y': 245},
    {'type': 'text', 'text': '08123456789', 'x': 840, 'y': 245},
]

# HALAMAN 4 (SNAP VIRTUAL ACCOUNT)
page4 = [
    # Centang SNAP Virtual Account (Create, Update, Inquiry, Payment, Status)
    {'type': 'check', 'x': 230, 'y': 825}, # Create VA
    {'type': 'check', 'x': 378, 'y': 825}, # Update VA
    {'type': 'check', 'x': 530, 'y': 825}, # Inquiry VA
    {'type': 'check', 'x': 680, 'y': 825}, # Payment callback
    {'type': 'check', 'x': 230, 'y': 865}, # Inquiry status
    
    # URL Callback DEV & PROD
    {'type': 'text', 'text': 'https://api.sipanda.online/api/v1/payment/bni/callback', 'x': 420, 'y': 902},
    {'type': 'text', 'text': 'https://api.baubaukota.go.id/api/v1/payment/bni/callback', 'x': 430, 'y': 925},
]

# HALAMAN 5 (QRIS MPM)
page5 = [
    # Centang QRIS
    {'type': 'check', 'x': 230, 'y': 845}, # Generate QR
    {'type': 'check', 'x': 380, 'y': 845}, # Payment Notification
    {'type': 'check', 'x': 530, 'y': 845}, # Query Payment
    
    # URL Callback QRIS DEV & PROD
    {'type': 'text', 'text': 'https://api.sipanda.online/api/v1/payment/bni/qris/callback', 'x': 420, 'y': 880},
    {'type': 'text', 'text': 'https://api.baubaukota.go.id/api/v1/payment/bni/qris/callback', 'x': 430, 'y': 910},
]

# HALAMAN 6 (Limit Hit)
page6 = [
    # Klasifikasi HIGH
    {'type': 'check', 'x': 50, 'y': 885},
]

# HALAMAN 7 (Tipe VA)
page7 = [
    # Tipe VA: Billing
    {'type': 'check', 'x': 390, 'y': 135},
]

# HALAMAN 8 (Whitelist IP)
page8 = [
    {'type': 'text', 'text': 'SNAP VA & QRIS Dinamis', 'x': 50, 'y': 190},
    {'type': 'check', 'x': 260, 'y': 185}, # Tambah
    {'type': 'text', 'text': '<IP PUBLIC BIZNET DEV>', 'x': 410, 'y': 190},
    {'type': 'text', 'text': '<IP PUBLIC BIZNET PROD>', 'x': 700, 'y': 190},
]

if __name__ == "__main__":
    draw_on_page(1, page1)
    draw_on_page(2, page2)
    draw_on_page(4, page4)
    draw_on_page(5, page5)
    draw_on_page(6, page6)
    draw_on_page(7, page7)
    draw_on_page(8, page8)
    
    print(f"\nSelesai! Gambar yang sudah diisi disimpan di folder: {OUTPUT_DIR}")
    print("Silakan buka file _terisi.jpg tersebut. Jika posisi meleset, Anda bisa mengubah angka 'x' dan 'y' di file script ini dan menjalankannya lagi.")
