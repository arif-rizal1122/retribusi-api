# Layanan Pajak Dishub & Pola Petugas M-PAD

Dokumen ini menjelaskan detail mengenai **Layanan Pajak Dinas Perhubungan (Dishub)** dan **Pola Operasional Petugas Lapangan** yang telah didokumentasikan dan diterapkan secara langsung ke dalam ekosistem M-PAD (khususnya pada aplikasi `retribusi-petugas`, `retribusi-api`, dan `retribusi-admin`).

---

## 1. Layanan Retribusi Parkir Dishub di M-PAD

Sesuai dengan dokumen regulasi (`BAUBAU_REGULATORY_MASTER.md`) dan arsitektur sistem (`MEGA_DOCUMENTATION.md`), retribusi parkir bukan dikelola oleh Bapenda, melainkan menjadi wewenang **Dinas Perhubungan (opd_id=2)**.

Penerapannya dalam sistem M-PAD:

- **Sentralisasi Pendapatan (Automated Billing):**
  Meskipun transaksinya bersifat receh/mikro (misal: Rp2.000), sistem backend `ParkingController` didesain untuk otomatis men-*generate* *record* tagihan (`Bill`) ke tabel utama. Hal ini krusial agar laporan keuangan BPK/SIPD tetap konsisten dalam satu format neraca, tanpa membedakan pajak besar (seperti Reklame) dan retribusi kecil (Parkir).
  
- **Manajemen Shift & Wallet Jukir:**
  Juru Parkir (Jukir) bekerja berdasarkan siklus **Satu Shift per Hari** (menggunakan tabel `jukir_wallets`). Sistem merekam jumlah kendaraan masuk (R2/R4), total uang tunai yang dipegang Jukir, dan total uang yang langsung masuk Kas Daerah via QRIS. Pada akhir hari, Jukir wajib melakukan "Tutup Shift" sebagai bentuk pertanggungjawaban (rekonsiliasi).

- **Live Dashboard Pengawasan:**
  Aktivitas titik parkir, status shift Jukir yang sedang aktif, serta aliran dana terpantau secara *real-time* oleh Admin Dishub / Pengawas melalui widget khusus di `PengawasDashboard.tsx` (`retribusi-admin`).

---

## 2. Pola Petugas Lapangan (Officer Workflow & UI/UX)

Berdasarkan dokumen pedoman `field_efficiency/SKILL.md` serta implementasi `ParkingQuickCashier.tsx`, pola kerja petugas M-PAD difokuskan pada **"Zero-Friction & High Speed"** untuk meminimalisir interaksi manusia dengan layar aplikasi.

Pola operasional yang diterapkan:

### A. Pola *Quick-Tap* (Kasir Cepat Jukir)
- **Satu Kali Sentuh:** Petugas Jukir tidak perlu mengisi form panjang. Antarmuka menggunakan desain *Quick-Tap*—hanya menekan tombol ikon **Motor (Rp 2.000)** atau **Mobil (Rp 3.000)**, dan sistem langsung mencatatnya ke database.
- **QRIS Offline Dinamis (Zero-Latency):** Untuk meminimalisir ketergantungan sinyal internet lapangan, QRIS di-generate 100% secara lokal (*offline*) di perangkat petugas. Aplikasi membaca payload QRIS statis, lalu menginjeksi nominal (Tag 54) dan menyuntikkan *Reference ID* unik (Tag 62), serta menghitung ulang algoritma CRC16 (Tag 63) seketika dalam hitungan milidetik.
- **Referral Counter:** Sistem menggunakan generator unik `MM-XXXX` (reset bulanan otomatis berdasar RTC perangkat) sebagai referensi bukti transaksi tanpa perlu koneksi ke server pusat untuk mengambil nomor antrean.

### B. Pola *Smart QR Redirection* & *Auto-Inquiry* (Petugas Pendata/Pajak)
- **Context-Aware Scanner:** Petugas tidak perlu memilih "Saya mau scan PBB" atau "Saya mau scan SKRD". Petugas cukup membuka kamera, dan aplikasi akan membaca *payload* QR. Jika QR dari blanko PBB, aplikasi otomatis membuka halaman pendataan PBB. Jika QR berisi ID Tagihan, otomatis membuka halaman *Billing/Payment Modal*.
- **Target < 15 Detik:** Desain arsitekturnya mewajibkan bahwa dari saat petugas menyorot kamera ke QR hingga muncul modal pembayaran, petugas tidak perlu menekan tombol pencarian. *Inquiry* (pencarian data) tertrigger otomatis sehingga transaksi bisa selesai di bawah 15 detik.

### C. Pola Bukti Kerja (Digital LKOK & Thermal Print)
- **Bukti Cetak / Digital:** Jukir dapat mencetak struk retribusi menggunakan printer Thermal Bluetooth kecil yang dibawa di pinggang, sehingga wajib pajak mendapat bukti fisik. 
- **LKOK (Lembar Kerja Objek Khusus):** Untuk petugas peninjau lapangan (pajak reklame, PBB), setiap kunjungan menghasilkan form observasi digital yang langsung terintegrasi dengan koordinat GPS dan foto (*geotagging*).

### D. Pola Penugasan berbasis *Geofencing* (Rencana/Protokol)
- **Task Sorting:** Tugas patroli atau tagihan tunggakan diurutkan di aplikasi petugas berdasarkan jarak (radius GPS) terdekat dari titik berdiri petugas saat itu, meminimalisir waktu mondar-mandir di lapangan.
- **Location Lockdown:** Pembatasan aksi "Bayar/Verifikasi" jika petugas tidak berada secara fisik di titik koordinat objek pajak (untuk menghindari fraud, petugas tidak bisa mencatat kunjungan dari warung kopi/rumah).

> [!NOTE]
> Secara garis besar, ekosistem untuk **Petugas** di mPaD tidak dirancang seperti aplikasi formulir biasa, melainkan didesain selayaknya **mesin EDC kasir + GPS Tracker**—harus cepat, andal saat minim sinyal (QRIS offline), dan termonitor secara *real-time* oleh instansi (Bapenda/Dishub).
