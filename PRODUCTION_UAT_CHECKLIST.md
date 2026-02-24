# Panduan Lengkap Pengujian (UAT) Menuju Production (api.sipanda.online)

Dokumen ini berisi daftar langkah (checklist) pengujian menyeluruh untuk memastikan server *Production* MITRA PAD siap dan aman digunakan oleh Wajib Pajak dan Petugas. Lakukan pengujian ini setiap kali akan melakukan *Go-Live* atau peluncuran fitur besar.

---

## Daftar Modul Pengujian

Untuk memudahkan tim QA / Penguji memverifikasi kesiapan sistem, pedoman UAT ini telah kami pecah secara merinci ke dalam 6 Dokumen Modul Pengujian terpisah.

Silakan klik setiap tautan di bawah ini untuk melihat prosedur langkah demi langkah (termasuk kriteria sukses dan data *dummy* yang digunakan) untuk masing-masing fase:

1. 📂 **[Tahap 1: Persiapan Deployment](testing/01-Persiapan-Deployment.md)**  
   *(Verifikasi versi kode, setelan environment variables, optimasi cache server, dan eksekusi migrasi skema tabel Database).*

2. 🔐 **[Tahap 2: Pengujian Infrastruktur & Keamanan (Security Smoke Test)](testing/02-Keamanan-Infrastruktur.md)**  
   *(Prosedur meretas/mensimulasikan serangan DDOS untuk menguji Rate Limiting Nginx, proteksi isolasi file rahasia `.env`, dan enkripsi lapis baja HTTPS).*

3. 💼 **[Tahap 3: Pengujian Alur Utama Pajak / Retribusi (E2E Business Flow)](testing/03-Alur-Utama-E2E.md)**  
   *(Simulasi skenario penuh dunia nyata: Wajib pajak mendaftar via App HP -> Bapenda Admin menerbitkan SKPD -> Petugas Lapangan Keliling menagih lunas -> Dashboard Omzet tersinkron real-time).*

4. 📄 **[Tahap 4: Pengujian Fitur Kritis & Edge Cases](testing/04-Fitur-Kritis-Edge-Cases.md)**  
   *(Skenario uji validitas Tanda Tangan Elektronik QR Code, Performa Render grafis Peta Satelit GIS, serta penghapusan/Amnesty/Diskon tagihan anomali).*

5. 📱 **[Tahap 5: Pengujian Lintas Layar Perangkat (Cross-Device UAT)](testing/05-Cross-Device-UAT.md)**  
   *(Metode menguji keandalan sistem memakai ragam tipe Web Browser ekstrem dan memvalidasi tampilan responsif layar sempit Petugas Keliling).*

6. 📡 **[Tahap 6: Pemantauan Hari Pertama (Go-Live Monitoring)](testing/06-Go-Live-Monitoring.md)**  
   *(Instruksi untuk Admin Server via SSH Panel Backend dalam merekam log _Crash/Exception_ dan antisipasi kebocoran _RAM/CPU (Memory Leak)_ pada hari sibuk rilis massal).*

---

> **Catatan Tim QA:**  
> Harap centang parameter lulus uji (*Passed*) Anda untuk masing-masing modul di atas ketika telah diselesaikan dengan status _"Lulus Tanpa Cacat"_. Jika ditemukan cacat (_Bug/Vulnerability_), segera putar balik (*rollback*) atau tahan rilis kepada publik hingga tim pengembang menyuntikkan _Hot-Fix Code_ perbaikan sasarannya.
