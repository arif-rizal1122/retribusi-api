# Tahap 6: Pemantauan Hari Pertama (Go-Live Monitoring)

Jika tahap 1 hingga 5 dinyatakan Lulus Tanpa Syarat (Green-lighted), persilahkan seremonial *Soft-Launch* atau rilis aplikasi *End-user* untuk dipakai bekerja publik (Bapenda, OPD, dan khalayak umum/Wajib Pajak se-daerah).

Pada fase kerawanan tinggi **24 jam pertama paska pembukaan akses (Go-Live)**, ikuti mandat monitoring berikut bagi para Staff Server (IT Developer / SysAdmin):

## 6.1. Pantauan Eksekusi Log Exception (Real-time Tailing)
- **Instruksi**: Sang Administrator Backend (Tim Anda) wajib bersiaga membuka _Console SSH Client_ terkoneksi ke IP Publik VPS Anda `157.10.252.74`. Masuk sebagai user `mpad` atau `root`.
- **Tindakan**: Biarkan tab layar hitam konsol VPS mengeksekusi hidup mantra ini tanpa terputus siang ini:
  ```bash
  tail -f /home/mpad/retribusi-api/storage/logs/laravel.log
  ```
- **Kondisi Normal/Siaga**: Tab hitam terminal sunyi (tak ada log aneh merayap cepat) atau sesekali cuma melempar sebaris *Info Log* wajar.
- **Kondisi Kritis Bertindak**: Jika tiba-tiba layar terminal Anda memuntahkan tarian teks sekuensial panjang puluhan baris yang diawali bait `[stacktrace]..........` secara masif, atau bait keras kemerah-merahan `Fatal Error:` / `SQLSTATE[...] SQL Syntax Exception`, **BERHENTI!** Cepat catat URL Rute-nya, *rollback* rute tersebut atau _Hot-Fix Code_ detik itu juga.

## 6.2. Pantauan Pembengkakan Ram Tercekik (Memory Leak Hardware Metric)
- **Instruksi**: Buka tab *SSH* kedua ke server API VPS Nginx Anda berdampingan.
- **Tindakan**: Keluarkan komando diagnostik detak jantung _CPU/RAM Hardware_:
  ```bash
  htop
  ```
  *(Atau jalankan terus manual berkala sekedar info `free -h -s 5`)*.
- **Waspadai Resiko**: Di saat gerbang diumumkan terbuka untuk PNS/Masyarakat massal login sistem API, bar garis Memory (RAM Hijau/Kuning Bar) bisa tiba-tiba naik tajam secara tak terkendali mentok di batas _Swap Space_.
- Jika *htop* memperlihatkan angka CPU `100% Core Load` melulu dari *Pool PHP-FPM / MySQLd*, dan RAM habis, ini terindikasi aplikasi API retribusi kekurangan sumber daya (terkena *Memory Leaks* atau *Bad N+1 Laravel Query* mendadak).
  - *Tindakan Mitigasi Darurat Taktikal*: Segera _restart_ daemon engine FPM ( `sudo systemctl restart php8.x-fpm` & `nginx` ). Lalu lapor koordinator pelan-pelan cari solusinya merapihkan baris kode *Query Controller* yang bikin berat server melambat drastis itu di jam kosong trafik (Malam hari).

Jika tak ada kendala luar biasa dari Monitor Server selama matahari tenggelam sampai terbit ini?

**SELAMAT! INSTALASI API RETRIBUSI ANDA 100% SUKSES TANGGUH DALAM SKALA PRODUKSI KELAS NEGARA!**
