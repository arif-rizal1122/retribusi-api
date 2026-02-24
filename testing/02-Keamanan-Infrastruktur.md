# Tahap 2: Pengujian Infrastruktur & Keamanan (Security Smoke Test)

Verifikasikan bahwa benteng perlindungan yang dipasang di Nginx bekerja secara aktif menangkis serangan.

## 2.1. Ujian Akses File Rahasia
- **Langkah**: Buka browser (disarankan mode *Incognito* / *Private*). Ketik alamat langsung ke file konfigurasi rahasia: `https://api.sipanda.online/.env`.
- **Hasil yang Diharapkan**: Browser harus menampilkan halaman putih kosong dengan tulisan tegas "403 Forbidden" (yang dihasilkan oleh Nginx). 
- **Peringatan**: Jika browser malah mengunduh (download) file teks `.env` tersebut, **SEGERA MATIKAN SERVER** karena kredensial database bocor.

## 2.2. Ujian Dokumentasi API Terselubung
- **Langkah**: Buka tab baru, arahkan ke rute publik dokumentasi: `https://api.sipanda.online/docs`.
- **Hasil yang Diharapkan**: Halaman harus *blank* dan menampilkan teks JSON murni yang berisi `{"message": "Unauthenticated."}` dengan balasan status HTTP `401 Unauthorized`. 
- **Tujuan**: Ini membuktikan bahwa dokumentasi API (struktur endpoint, parameter wara-wiri server) tidak lagi bocor atau bisa diintip sembarang orang di internet publik tanpa token.

## 2.3. Ujian Anti-Spam / Anti-DDoS (Rate Limiting)
- **Langkah Utama**: Gunakan aplikasi seperti *Postman*, *Insomnia*, atau alat *curl* CLI.
- **Aksi**: Tembakkan _HTTP POST / GET Request_ berulang-ulang ke alamat login `https://api.sipanda.online/api/login` sebanyak minimal **65 kali secara brutal dalam kurun waktu kurang dari 1 menit**.
- **Hasil yang Diharapkan**: 
  - Pada *request* ke-1 hingga 60, server merespons normal (misal 401 atau 200).
  - Pada *request* ke-61, HTTP Response code berubah mendadak menjadi `429 Too Many Requests`.
- **Tujuan**: Membuktikan Laravel *Throttle limit* di rute Auth sukses meredam serangan Brute-Force pencurian password.

## 2.4. Ujian Integritas Sertifikat & Header HSTS
- **Langkah**: Buka front-end Web Admin. Klik kanan layar -> **Inspect Element** (F12) -> Buka tab **Network**. *Refresh* halamannya.
- **Hasil yang Diharapkan**:
  - Indikator gembok HTTPS di dekat URL Web menyala hijau terkunci penuh.
  - Saat meng-klik salah satu *Request API* di tab Network, pada bagian *Response Headers* harus tertera tulisan `Strict-Transport-Security: max-age=31536000; includeSubDomains`. Ini berarti jalur dipaksa 100% menggunakan koneksi terenkripsi tingkat tinggi.
