[5/7, 19.14] Pak ‌‌ ‌🇲‌‌‌‌🇦‌‌🇸‌‌🇸‌‌🇦‌‌🇩‌ Komander: Berdasarkan arsitektur terbaru M-PAD (Management Information Tax, Retribution, and Asset Pendapatan Asli Daerah) yang telah mengintegrasikan pilar Pajak, Retribusi, dan Aset, berikut adalah pemutakhiran menyeluruh mengenai siapa saja (aktor/pengguna) yang dapat menggunakan dan mengakses sistem ini, lengkap dengan batasan hak aksesnya (User Access Matrix):
I. Internal Pemerintah Daerah (Sisi Pengelola & Pengawas)
Kelompok pengguna ini beroperasi di bagian belakang (back-end) untuk mengelola data, memvalidasi transaksi, dan mengawasi kebocoran anggaran.
1. Badan Pendapatan Daerah (Bapenda)
    * Peran: Pengendali Utama (Super Admin / Main Operator Perpajakan).
    * Hak Akses: Memiliki akses penuh untuk melihat seluruh database wajib pajak, menerbitkan Surat Pemberitahuan Pajak Terutang (e-SPPT/e-SPTPD), memvalidasi laporan omset bulanan usaha, mengunci data hasil uji petik, dan merekonsiliasi data keuangan yang masuk dari Bank Persepsi.
2. Badan Pengelola Keuangan dan Aset Daerah (BPKAD) - Khusus Bidang Aset
    * Peran: Pengelola Data Inventaris dan Pemanfaatan Kekayaan Daerah.
    * Hak Akses: Mengakses modul Manajemen Aset untuk menginput Kartu Inventaris Barang (KIB) daerah, memetakan koordinat aset milik Pemda (tanah, bangunan, ruko), memasukkan data legalitas (sertifikat), serta mengelola draf kontrak sewa/kerja sama pemanfaatan (KSP) aset dengan pihak ketiga.
3. OPD / Dinas Teknis Penghasil Retribusi (Dishub, Disperindag, Dinas Pariwisata, dll.)
    * Peran: Eksekutor Lapangan Pemungutan Retribusi Sektoral.
    * Hak Akses: Terbatas pada kluster retribusi dinas masing-masing (Multi-Tenant Access). Petugas Dinas Perhubungan hanya bisa mengakses dashboard retribusi parkir/terminal; petugas Disperindag hanya melihat data sewa kios/los pasar. Mereka menginput realisasi harian dan memantau apakah target retribusi dinas mereka sudah tercapai atau belum.
4. Kepala Daerah (Walikota/Bupati) & Sekretaris Daerah (Sekda)
    * Peran: Pengambil Kebijakan Strategis Evaluasi PAD.
    * Hak Akses: Executive Dashboard (Read-Only Tingkat Tinggi). Tidak bisa mengubah atau menghapus data, tetapi dapat memantau grafik makro real-time dari gabungan sektor Pajak, Retribusi, dan Aset. Mereka menggunakan data ini untuk mengevaluasi kinerja kepala dinas, melihat tren pertumbuhan ekonomi, dan menyusun proyeksi anggaran 3–5 tahun ke depan.
5. Inspektorat / Badan Pengawas (Internal Auditor)
    * Peran: Auditor Akuntabilitas dan Pencegahan Korupsi.
    * Hak Akses: Akses pengawasan (Audit Trail). Mereka dapat melacak riwayat log transaksi untuk melihat apakah ada manipulasi data, memantau piutang pajak yang macet, serta memverifikasi kontrak-kontrak aset daerah yang akan atau sedang berjalan demi mencegah kerugian negara.
II. Eksternal Pemerintah (Sisi Pembayar & Mitra Strategis)
Kelompok pengguna ini mengakses sistem melalui pintu depan (front-end / aplikasi mobile / portal web) untuk melakukan transaksi dan pelayanan.
1. Wajib Pajak (Pelaku Usaha & Masyarakat Umum)
    * Peran: Subjek Pembayar Pajak Daerah.
    * Hak Akses: Mengakses akun personal (Single Sign-On). Mereka dapat mendaftarkan usaha baru untuk mendapatkan NPWPD, melaporkan omset bulanan secara mandiri (e-SPTPD), mengecek tagihan PBB-P2, mengunduh Kode Bayar (Billing Code), dan menyimpan bukti pembayaran digital (e-Resi). Mereka tidak bisa melihat data keuangan atau omset dari pelaku usaha lain.
2. Penyewa / Investor Aset Daerah (Mitra Pihak Ketiga)
    * Peran: Mitra Pemanfaatan Kekayaan Daerah.
    * Hak Akses: Mengakses portal informasi aset daerah yang bersifat idle atau terbuka untuk dikerjasamakan. Melalui akun kemitraan, mereka dapat mengajukan permohonan sewa, memantau masa aktif kontrak sewa bangunan/lahan Pemda, dan menerima notifikasi tagihan jatuh tempo sewa aset untuk langsung dibayarkan melalui sistem.
3. Bank Pembangunan Daerah (BPD) & Lembaga Keuangan / Payment Gateway
    * Peran: Jembatan Transaksi Finansial (Host-to-Host).
    * Hak Akses: Akses terbatas melalui API (Application Programming Interface). Server bank terhubung langsung dengan core-system M-PAD untuk membaca validasi kode billing ketika wajib pajak membayar di ATM, Mobile Banking, atau QRIS, serta mengirimkan konfirmasi instan (Payment Settlement) balik ke sistem M-PAD bahwa uang telah masuk ke Kas Daerah.
4. Instansi Vertikal (BPN & Disdukcapil) - Tahap Integrasi Lanjutan
    * Peran: Validasi dan Penyelarasan Data Makro.
    * Hak Akses: Melalui interkoneksi data data-sharing. Badan Pertanahan Nasional (BPN) mengakses modul BPHTB untuk memvalidasi keabsahan setoran pajak sebelum sertifikat tanah diterbitkan, sementara Disdukcapil menyediakan gerbang validasi NIK/NIB guna memastikan wajib pajak atau penyewa aset adalah entitas yang valid dan nyata.
Mengapa Pemutakhiran Akses Ini Penting untuk Skala "Sultra Connect"?
Dengan pemetaan hak akses yang klir dan berbasis peran (Role-Based Access Control), jika kelak M-PADditarik menjadi ekosistem Sultra Connect tingkat provinsi, sistem tinggal menduplikasi arsitektur keamanan ini. Pemerintah Provinsi (Gubernur) nantinya cukup ditambahkan sebagai Aktor Pengawas Makro yang memegang hak akses komparatif untuk memantau performa Pajak, Retribusi, dan Aset dari 17 kabupaten/kota se-Sultra secara terpusat.

Jika M-PAD diibaratkan sebagai embrio—sebuah cetak biru (blueprint) sistem pemantauan pendapatan yang sudah teruji di tingkat operasional kota—maka Sultra Connect adalah ekosistem utuh yang mengintegrasikannya ke level makro.
Dalam arsitektur tata kelola digital, lompatan dari M-PAD ke Sultra Connect bukan sekadar mereplikasi aplikasi ke daerah lain, melainkan membangun sebuah Satu Data Fiskal Regional yang menjembatani seluruh Pemerintah Daerah (Pemda) se-Sulawesi Tenggara.
Berikut adalah analisis sejauh mana Sultra Connect dapat berperan strategis jika menggunakan M-PAD sebagai mesin penggerak utamanya:
1. Standardisasi Tata Kelola Pendapatan se-Sultra
Salah satu masalah utama dalam digitalisasi daerah adalah fragmentasi sistem. Kabupaten A menggunakan aplikasi X, Kabupaten B menggunakan aplikasi Y, dan akibatnya data tidak bisa saling "berbicara".
* Peran Sultra Connect: Dengan menjadikan M-PAD sebagai embrio, Sultra Connect berperan sebagai platform White-Label (sistem terstandarisasi yang siap pakai). Pemerintah Provinsi dapat memfasilitasi kabupaten/kota se-Sultra untuk mengadopsi modul M-PAD yang sama.
* Dampaknya: Biaya investasi teknologi di tingkat kabupaten/kota akan pangkas secara drastis karena mereka tidak perlu membangun sistem dari nol. Cukup melakukan kustomisasi parameter tarif pajak/retribusi sesuai Perda masing-masing daerah.
2. Konsolidasi Fiskal & Dashboard Gubernur (Satu Data Sultra)
Selama ini, Pemerintah Provinsi sering kali kesulitan memantau kapasitas fiskal riil dari 17 kabupaten/kota di bawahnya secara real-time untuk keperluan evaluasi atau penyaluran bantuan keuangan/dana bagi hasil.
* Peran Sultra Connect: Sultra Connect akan bertindak sebagai hub data makro. Ketika seluruh Pemda menggunakan embrio M-PAD di tingkat lokal, seluruh data transaksi, realisasi, dan proyeksi PAD dari setiap daerah akan mengalir ke dalam Dashboard Fiskal Eksekutif Sultra Connect.
* Dampaknya: Gubernur dan tim TP2DD (Tim Percepatan dan Perluasan Digitalisasi Daerah) tingkat provinsi memiliki basis data yang sangat akurat untuk menganalisis pertumbuhan ekonomi regional, mendeteksi daerah yang realisasinya seret, dan merumuskan kebijakan insentif fiskal secara presisi berdasarkan tren data 3-5 tahunan.
3. Integrasi Pajak Sektoral Lintas Batas (Interoperabilitas Tingkat Tinggi)
Banyak potensi ekonomi di Sultra yang sifatnya lintas wilayah atau melibatkan komoditas strategis yang dikelola bersama (seperti sektor maritim, logistik, pertambangan, dan distribusi bahan bakar).
* Peran Sultra Connect: Di sinilah Sultra Connect mengambil peran yang tidak bisa dilakukan oleh M-PAD mandiri. Sultra Connect dapat mengintegrasikan modul perpajakan sektoral berskala besar. Sebagai contoh, data pergerakan kapal kargo, tongkang mineral, atau distribusi Marine Fuel Oil (MFO) di pelabuhan-pelabuhan Sultra (seperti Baubau, Kendari, atau Kolaka) dapat diintegrasikan Host-to-Host(H2H) dengan data pelabuhan (Syahbandar) dan database pajak provinsi.
* Dampaknya: Potensi kebocoran pajak di sektor komoditas dan maritim yang melibatkan multidaerah dapat ditekan. Kabupaten penghasil dan provinsi bisa berbagi data secara adil untuk optimalisasi Pajak Daerah dan Retribusi Daerah (PDRD).
4. Kemudahan Layanan bagi Wajib Pajak (Single Sign-On Warga Sultra)
Dari sisi masyarakat atau pelaku usaha yang memiliki aset atau bisnis di beberapa kabupaten berbeda di Sultra (misalnya, pengusaha yang memiliki ruko di Baubau sekaligus di Buton dan Kendari), sistem yang terpisah sangat menyulitkan kepatuhan pajak.
* Peran Sultra Connect: Sultra Connect bertindak sebagai Super Apps layanan publik masyarakat dengan fitur Single Sign-On (SSO). Pelaku usaha cukup memiliki satu akun Sultra Connect untuk memantau dan membayar seluruh kewajiban pajaknya (PBB, Pajak Restoran, hingga Pajak Kendaraan Bermotor) di wilayah kabupaten/kota mana pun di Sultra.
* Dampaknya: Indeks Kemudahan Berusaha (Ease of Doing Business) di Sulawesi Tenggara akan melonjak tajam karena birokrasi pembayaran pajak diringkas menjadi satu genggaman.
Tantangan Arsitektur yang Harus Disiapkan
Agar embrio M-PAD sukses bermutasi menjadi Sultra Connect yang diadopsi 17 kabupaten/kota, ada tiga langkah hulu yang harus disiapkan dalam pengembangan sistemnya:
1. Regulasi Payung Hukum (Pergub/SK Bersama): Diperlukan regulasi di tingkat provinsi yang memayungi replikasi dan pemanfaatan bersama sistem ini agar aspek legalitas pembagian data (API sharing) antar-daerah klir.
2. Arsitektur Cloud yang Aman: Sistem harus diletakkan pada Pusat Data Nasional (PDN) atau clouddaerah yang dikelola Provinsi dengan standar keamanan siber yang ketat, mengingat data yang dikelola adalah data transaksi keuangan daerah.
3. Bridge Procedure & API: Memastikan tim pengembang membangun arsitektur M-PAD dengan prinsip Open API, sehingga ketika ditarik ke ekosistem Sultra Connect, sistem mampu menjembatani data dari berbagai instansi vertikal (seperti Bank Pembangunan Daerah, BPN, dan Korlantas/Samsat) secara mulus.
[5/7, 19.14] Pak ‌‌ ‌🇲‌‌‌‌🇦‌‌🇸‌‌🇸‌‌🇦‌‌🇩‌ Komander: Karena saya amati Bapenda Prov seolah tidur dengan maksud surat kita, narasi diatas sdh diteruskan ke Sekda Prov, mrk lagi pelajari untuk hal tersebut, dan bila presisi dengan RPJMD Sultra maka mrk akan masuk konsolidasi besar.

Demikian
[5/7, 19.17] muhdanfyan: Bang, ini luar biasa. Aiman tangkap gambaran besarnya:

---

*Pak Komander udah kirim blueprint M-PAD → Sultra Connect ke Sekda Provinsi Sultra, dan mereka lagi pelajari.*

Ini bukan sekadar info — ini titik balik. Dari yg Aiman baca:

*Apa yg terjadi:*
- Pak Komander kirim narasi arsitektur user access matrix + visi Sultra Connect
- Bapenda Provinsi dianggap "tidur" — ga respon maksud surat sebelumnya
- Tapi Sekda Provinsi Sultra *respon* — mereka pelajari, dan kalo presisi sama RPJMD, bakal masuk konsolidasi besar

*Kenapa ini penting:*
Dari analisis Aiman:
1. *M-PAD bukan lagi proyek kota* — udah naik kelas ke level provinsi
2. *Sekda yg pegang* — bukan eselon bawah. Kalo Sekda bilang "pelajari", itu udah masuk meja strategis
3. *RPJMD jadi pintu masuk* — kalo arsitektur M-PAD presisi sama rencana pembangunan daerah, ini bukan kontrak proyek biasa, tapi *mandat sistem*

*Yang perlu Bang Dadan siapin:*
- Arsitektur Open API M-PAD harus mature — kalo nanti 17 kabupaten/kota mau connect, API harus siap
- Dokumentasi teknis buat replikasi (white-label) — biar kabupaten lain tinggal pakai
- Bridge procedure ke database provinsi — koneksi lintas domain

Ini momen strategis, Bang. M-PAD dari embrio naik jadi backbone Sultra Connect. Kalo sekda udah ngomong RPJMD, artinya ini bukan omongan ringan.

Ada yg perlu Aiman bantu siapkan dari sisi teknis/dokumentasi? 🫡
[5/7, 19.20] Pak ‌‌ ‌🇲‌‌‌‌🇦‌‌🇸‌‌🇸‌‌🇦‌‌🇩‌ Komander: Jika kita melihat lanskap digitalisasi pemerintahan di Indonesia saat ini, memang ada beberapa aplikasi dan ekosistem di tingkat daerah maupun nasional yang **sudah beroperasi secara nyata (live)** dan telah melampaui atau merealisasikan apa yang saat ini sedang direncanakan untuk **Sultra Connect**.

Mempelajari sistem-sistem yang sudah matang ini sangat penting sebagai tolok ukur (*benchmark*) agar cetak biru Sultra Connect dapat mengantisipasi tantangan arsitektur sejak dini.

Berikut adalah beberapa aplikasi sejenis di Indonesia yang telah melampaui fase rencana dan berhasil mengintegrasikan pajak, retribusi, hingga layanan lintas wilayah:

---

## 1. Jabar Super Apps (Sapawarga) – Provinsi Jawa Barat

Jika Sultra Connect direncanakan sebagai *Super Apps* berbasis *Single Sign-On* (SSO) untuk warga Sulawesi Tenggara, maka **Sapawarga** adalah perwujudan yang sudah berjalan penuh di level provinsi.

* **Mengapa Ini Melampaui Rencana?**
* **SSO Sektor Fiskal & Layanan Publik:** Sapawarga berhasil menyatukan pembayaran Pajak Kendaraan Bermotor (Sambara), pencarian kerja, hingga layanan kesehatan dalam satu akun warga.
* **Integrasi Horizontal & Vertikal:** Sistem ini menghubungkan data dari Bapenda Provinsi, Korlantas Polri, Jasa Raharja, hingga Bank BPD (Bank bjb).
* **Pelajaran untuk Sultra Connect:** Jabar memisahkan urusan operasional harian yang rumit di tingkat kota/kabupaten, namun menarik data transaksionalnya ke level provinsi via API untuk memberikan *one-stop service* bagi warga.



---

## 2. ETax & Dashboard Pajak Daerah – DKI Jakarta (Bappenda DKI)

DKI Jakarta memiliki kekhasan karena statusnya sebagai Provinsi yang sekaligus memegang wewenang penuh atas Pajak Kabupaten/Kota. Sistem informasi perpajakan mereka adalah yang paling masif dan terintegrasi di Indonesia.

* **Mengapa Ini Melampaui Rencana?**
* **Otomatisasi Anti-Kebocoran (Uji Petik Digital):** Jakarta sudah mewajibkan *Tapping Box* dan sistem POS (*Point of Sales*) yang terintegrasi langsung ke e-SPTPD untuk pajak hotel, restoran, hiburan, dan parkir. Data transaksi usaha langsung mengalir ke server Bappenda secara *real-time*.
* **Integrasi BPN & Dukcapil Tingkat Tinggi:** Validasi BPHTB dan PBB-P2 dengan database Badan Pertanahan Nasional (BPN) serta data kependudukan sudah berjalan otomatis secara *Host-to-Host* (H2H).
* **Pelajaran untuk Sultra Connect:** Integrasi hulu dengan BPN sangat krusial untuk mencegah pemalsuan dokumen dan memastikan validitas objek pajak sebelum ditarik ke dasbor makro.



---

## 3. Simpada (Sistem Informasi Manajemen Pendapatan Daerah) – Berbagai Provinsi/Kabupaten

Banyak daerah di Indonesia yang bekerja sama dengan Bank Pembangunan Daerah (BPD) setempat atau penyedia teknologi (seperti milik PT Jasa Raharja atau Lintasarta) untuk menggunakan aplikasi *White-Label* bernama **Simpada**.

* **Mengapa Ini Melampaui Rencana?**
* **Standardisasi Antar-Daerah:** Di beberapa wilayah (seperti di Jawa Tengah dan Jawa Timur), sistem ini diadopsi secara massal oleh kabupaten/kota dengan standarisasi modul yang sama dari Bank BPD setempat.
* **Kemudahan Payment Gateway:** Sistem ini langsung terhubung dengan kanal pembayaran modern (QRIS, Tokopedia, Indomaret, Mobile Banking) secara *real-time settlement*.
* **Pelajaran untuk Sultra Connect:** Rencana Sultra Connect untuk menyediakan platform *White-Label* bagi 17 kabupaten/kota sangat bisa meniru pola distribusi Simpada yang didukung penuh oleh Bank BPD (dalam hal ini Bank Sultra).



---

## 4. Inovasi Sektor Maritim: INAPORTNET & E-Berthing (Kemenhub)

Untuk rencana spesifik Sultra Connect terkait integrasi sektor maritim (pajak komoditas, tongkang mineral, dan *Marine Fuel Oil* / MFO), platform nasional **Inaportnet** milik Kementerian Perhubungan adalah contoh sistem yang sudah berjalan.

* **Mengapa Ini Melampaui Rencana?**
* Inaportnet mengintegrasikan data pelabuhan, syahbandar, bea cukai, dan karantina untuk memantau keluar masuk kapal secara digital. Di beberapa pelabuhan maju, ini dikombinasikan dengan sistem retribusi pelayanan jasa pelabuhan daerah secara otomatis.
* **Pelajaran untuk Sultra Connect:** Sultra Connect tidak perlu membuat sistem pelacakan kapal dari nol. Arsitektur Sultra Connect sebaiknya dirancang untuk **menangkap data (data ingestion) dari API Inaportnet** atau Syahbandar setempat, kemudian mencocokkannya dengan volume MFO atau tonase (DWT) kapal untuk menghitung potensi pajaknya.



---

### Kesimpulan & Rekomendasi Strategis untuk Sultra Connect

Melihat aplikasi yang sudah matang di atas, **rencana arsitektur Sultra Connect berada di jalur yang sangat tepat dan visioner**. Lompatan yang direncanakan dari embrio M-PAD menuju Sultra Connect justru memiliki keunggulan kompetitif karena sejak awal sudah dirancang terintegrasi antara **Pajak, Retribusi, dan Aset**. Kebanyakan aplikasi di daerah lain masih terjebak dalam silo-silo terpisah (aplikasi pajak sendiri, aplikasi aset sendiri).

Untuk melampaui sistem-sistem yang ada sekarang, Sultra Connect dapat berfokus pada keunikan wilayahnya: **Akselerasi pada modul maritim (seperti Pajak Bahan Bakar Kendaraan Bermotor/PBBKB untuk kapal di atas 30 DWT) dan integrasi data komoditas tambang**, yang jarang dimiliki oleh provinsi di Pulau Jawa.

Apakah ada aspek spesifik dari arsitektur aplikasi di atas—seperti penerapan *Tapping Box* atau integrasi API dengan Bank BPD—yang ingin Anda bedah lebih dalam untuk memperkuat cetak biru M-PAD?
[5/7, 19.20] Pak ‌‌ ‌🇲‌‌‌‌🇦‌‌🇸‌‌🇸‌‌🇦‌‌🇩‌ Komander: Pelajari ini.

Ide menggantikan *Tapping Box* fisik dengan **Kalkulator Bisnis Digital** yang terintegrasi langsung dengan mekanisme *Auto-Debit* atau *Automatic Fund Transfer* (AFT) dari rekening pemilik usaha ke rekening penampung pajak adalah inovasi yang sangat progresif.

Skenario ini memindahkan instrumen pengawasan dari **perangkat keras** (*hardware-based monitoring*) ke **aliran dana perbankan** (*cashflow-based enforcement*).

Berikut adalah rancangan alur kerja, analisis regulasi, serta risiko teknis dan hukum atas skenario tersebut:

---

## 1. Alur Kerja (Workflow) Sub-Menu Kalkulator Bisnis & AFT

Dalam sub-menu Wajib Pajak (Pemungut/Merchant), proses kerja digital dirancang sebagai berikut:

* **Langkah 1: Input & Perhitungan (Kalkulator Bisnis)**
Saat konsumen membayar, kasir/pemilik usaha memasukkan nilai transaksi ke fitur "Kalkulator Bisnis" di aplikasi M-PAD. Sistem otomatis memisahkan komponen Pendapatan Usaha dan komponen Pajak Barang dan Jasa Tertentu (PBJT, misal 10%).
* **Langkah 2: Pembayaran Konsumen & Rekening Utama**
Konsumen membayar total tagihan melalui QRIS dinamis atau transfer bank yang disediakan sistem. 100% dana masuk terlebih dahulu ke **Rekening Operasional Pemilik Usaha** (untuk menjaga hak likuiditas harian merchant).
* **Langkah 3: Trigger Transaksi & Mekanisme AFT**
Begitu dana masuk ke rekening pemilik usaha, sistem M-PAD yang terhubung secara H2H dengan Bank BPD (Bank Sultra) mendeteksi transaksi sukses tersebut. Sistem langsung memicu perintah **AFT (Automatic Fund Transfer)** untuk memotong *hanya* komponen pajak (10%) dari rekening pemilik usaha.
* **Langkah 4: Rekening Penampungan & Settlement**
Dana hasil potong otomatis (AFT) tersebut masuk ke **Rekening Penampungan Sementara (Escrow Account) Pajak** di Bank BPD, sebelum akhirnya disetor secara massal ke Kas Daerah (Kasda) pada akhir hari atau periode tertentu secara otomatis (e-Resi terbit seketika).

---

## 2. Analisis Regulasi: Apakah Menyalahi Perundang-Undangan?

Secara prinsip dasar perpajakan, skenario ini **TIDAK menyalahi undang-undang, bahkan sangat sejalan** dengan semangat UU No. 1 Tahun 2022 (UU HKPD) dan Perpres No. 95 Tahun 2018 tentang SPBE (Sistem Pemerintahan Berbasis Elektronik). Namun, ada batasan hukum yang wajib dijaga ketat:

### Yang LEGAL dan Mendukung Undang-Undang:

* **Asas Titipan (Fidusia Pajak):** Pemilik usaha di sini bertindak sebagai **Pemungut Pajak** (*Withholding Tax*). Uang pajak yang dibayar konsumen pada hakikatnya bukan milik pengusaha, melainkan milik daerah. Menarik uang tersebut secara otomatis justru mempercepat penyerahan hak negara.
* **Elektronifikasi Transaksi Pemda (ETPD):** Satgas Nasional ETPD sangat mendorong inovasi digital yang mempercepat integrasi sistem keuangan daerah dengan bank persepsi untuk mencegah kebocoran anggaran (*leakage*).

### Yang Berpotensi Melanggar (Jika Tidak Diantisipasi):

* **Hak Privasi Perbankan (UU Perbankan):** Bank tidak boleh memotong atau memindahkan dana dari rekening nasabah tanpa persetujuan tertulis. Jika M-PAD melakukan AFT tanpa dasar, ini menyalahi aturan kerahasiaan bank.
* **Solusi Hukum:** Harus ada **Surat Kuasa Debet Rekening / Perjanjian Kerja Sama (PKS)** tertulis bertandatangan elektronik antara Pemilik Usaha, Pemda (Bapenda), dan Bank BPD saat pendaftaran akun M-PAD. Pengusaha secara sadar memberikan kuasa kepada sistem untuk melakukan AFT atas komponen pajak.

---

## 3. Risiko Atas Skenario Ini & Mitigasinya

Meskipun sistem ini efisien karena memangkas biaya pengadaan alat *Tapping Box*, terdapat risiko operasional dan teknis yang tinggi:

### A. Risiko Likuiditas dan *Gagal Debet* (Timing Risk)

* **Risiko:** Jika jeda waktu antara masuknya uang konsumen ke rekening pemilik usaha dengan eksekusi AFT terlalu lama, ada risiko uang tersebut langsung digunakan pemilik usaha untuk operasional lain (misal membayar *supplier*), sehingga saat sistem melakukan AFT, saldo di rekening pengusaha sudah tidak mencukupi (gagal debet).
* **Mitigasi:** Eksekusi AFT harus bersifat *real-time* atau *near-instant* (hitungan detik setelah dana konsumen masuk). Opsi lain adalah menggunakan mekanisme split-payment langsung di tingkat *Payment Gateway* (saat QRIS dipindai, 90% otomatis pecah ke rekening merchant, 10% otomatis pecah ke rekening penampung pajak).

### B. Risiko Resistensi Wajib Pajak (Kepatuhan Mandiri)

* **Risiko:** Karena "Kalkulator Bisnis" bersifat aplikasi tempat *input* manual, pemilik usaha bisa saja mengakali sistem dengan tidak memasukkan transaksi konsumen ke kalkulator tersebut (transaksi bawah meja). Berbeda dengan *Tapping Box* yang menangkap data langsung dari mesin kasir (POS).
* **Mitigasi:** Kalkulator Bisnis di M-PAD harus diintegrasikan lewat API dengan sistem POS/Kasir digital yang digunakan merchant (misal Moka, Majoo, dll.), atau Pemda tetap menjalankan **Uji Petik Digital** secara berkala (inspeksi mendadak mencocokkan jumlah pengunjung dengan transaksi yang di-input).

### C. Risiko Beban Biaya Transaksi (Biaya H2H/AFT)

* **Risiko:** Setiap transaksi *bridging/AFT* antar bank sering kali memicu biaya administrasi (skema BI-FAST atau kliring). Jika nilai transaksi pajak kecil (misal pajak bakso Rp2.000) namun biaya AFT-nya Rp2.500, maka sistem akan merugikan keuangan daerah atau wajib pajak.
* **Mitigasi:** Pemda harus membuat Perjanjian Kerja Sama khusus dengan Bank BPD (Bank Sultra) agar seluruh transaksi AFT dari rekening merchant ke rekening penampung pajak dikenakan **biaya Rp0,- (bebas biaya administrasi)** sebagai bentuk dukungan program ETPD.

---

### Kesimpulan Cetak Biru untuk Sultra Connect

Skenario **Kalkulator Bisnis + AFT** ini sangat layak diterapkan dan akan menjadi inovasi pionir. Skema ini sangat cocok ditarik ke skala **Sultra Connect**, karena menghemat miliaran rupiah APBD yang biasanya habis untuk membeli alat fisik *Tapping Box* untuk ribuan merchant di 17 kabupaten/kota. Kunci utamanya berada pada **Aspek Legal (Surat Kuasa Debet)** dan **Kesiapan API Bank BPD** untuk melakukan eksekusi *Split-Payment/AFT* secara instan.

Ide menggantikan *Tapping Box* fisik dengan **Kalkulator Bisnis Digital** yang terintegrasi langsung dengan mekanisme *Auto-Debit* atau *Automatic Fund Transfer* (AFT) dari rekening pemilik usaha ke rekening penampung pajak adalah inovasi yang sangat progresif.

Skenario ini memindahkan instrumen pengawasan dari **perangkat keras** (*hardware-based monitoring*) ke **aliran dana perbankan** (*cashflow-based enforcement*).

Berikut adalah rancangan alur kerja, analisis regulasi, serta risiko teknis dan hukum atas skenario tersebut:

---

## 1. Alur Kerja (Workflow) Sub-Menu Kalkulator Bisnis & AFT

Dalam sub-menu Wajib Pajak (Pemungut/Merchant), proses kerja digital dirancang sebagai berikut:

* **Langkah 1: Input & Perhitungan (Kalkulator Bisnis)**
Saat konsumen membayar, kasir/pemilik usaha memasukkan nilai transaksi ke fitur "Kalkulator Bisnis" di aplikasi M-PAD. Sistem otomatis memisahkan komponen Pendapatan Usaha dan komponen Pajak Barang dan Jasa Tertentu (PBJT, misal 10%).
* **Langkah 2: Pembayaran Konsumen & Rekening Utama**
Konsumen membayar total tagihan melalui QRIS dinamis atau transfer bank yang disediakan sistem. 100% dana masuk terlebih dahulu ke **Rekening Operasional Pemilik Usaha** (untuk menjaga hak likuiditas harian merchant).
* **Langkah 3: Trigger Transaksi & Mekanisme AFT**
Begitu dana masuk ke rekening pemilik usaha, sistem M-PAD yang terhubung secara H2H dengan Bank BPD (Bank Sultra) mendeteksi transaksi sukses tersebut. Sistem langsung memicu perintah **AFT (Automatic Fund Transfer)** untuk memotong *hanya* komponen pajak (10%) dari rekening pemilik usaha.
* **Langkah 4: Rekening Penampungan & Settlement**
Dana hasil potong otomatis (AFT) tersebut masuk ke **Rekening Penampungan Sementara (Escrow Account) Pajak** di Bank BPD, sebelum akhirnya disetor secara massal ke Kas Daerah (Kasda) pada akhir hari atau periode tertentu secara otomatis (e-Resi terbit seketika).

---

## 2. Analisis Regulasi: Apakah Menyalahi Perundang-Undangan?

Secara prinsip dasar perpajakan, skenario ini **TIDAK menyalahi undang-undang, bahkan sangat sejalan** dengan semangat UU No. 1 Tahun 2022 (UU HKPD) dan Perpres No. 95 Tahun 2018 tentang SPBE (Sistem Pemerintahan Berbasis Elektronik). Namun, ada batasan hukum yang wajib dijaga ketat:

### Yang LEGAL dan Mendukung Undang-Undang:

* **Asas Titipan (Fidusia Pajak):** Pemilik usaha di sini bertindak sebagai **Pemungut Pajak** (*Withholding Tax*). Uang pajak yang dibayar konsumen pada hakikatnya bukan milik pengusaha, melainkan milik daerah. Menarik uang tersebut secara otomatis justru mempercepat penyerahan hak negara.
* **Elektronifikasi Transaksi Pemda (ETPD):** Satgas Nasional ETPD sangat mendorong inovasi digital yang mempercepat integrasi sistem keuangan daerah dengan bank persepsi untuk mencegah kebocoran anggaran (*leakage*).

### Yang Berpotensi Melanggar (Jika Tidak Diantisipasi):

* **Hak Privasi Perbankan (UU Perbankan):** Bank tidak boleh memotong atau memindahkan dana dari rekening nasabah tanpa persetujuan tertulis. Jika M-PAD melakukan AFT tanpa dasar, ini menyalahi aturan kerahasiaan bank.
* **Solusi Hukum:** Harus ada **Surat Kuasa Debet Rekening / Perjanjian Kerja Sama (PKS)** tertulis bertandatangan elektronik antara Pemilik Usaha, Pemda (Bapenda), dan Bank BPD saat pendaftaran akun M-PAD. Pengusaha secara sadar memberikan kuasa kepada sistem untuk melakukan AFT atas komponen pajak.

---

## 3. Risiko Atas Skenario Ini & Mitigasinya

Meskipun sistem ini efisien karena memangkas biaya pengadaan alat *Tapping Box*, terdapat risiko operasional dan teknis yang tinggi:

### A. Risiko Likuiditas dan *Gagal Debet* (Timing Risk)

* **Risiko:** Jika jeda waktu antara masuknya uang konsumen ke rekening pemilik usaha dengan eksekusi AFT terlalu lama, ada risiko uang tersebut langsung digunakan pemilik usaha untuk operasional lain (misal membayar *supplier*), sehingga saat sistem melakukan AFT, saldo di rekening pengusaha sudah tidak mencukupi (gagal debet).
* **Mitigasi:** Eksekusi AFT harus bersifat *real-time* atau *near-instant* (hitungan detik setelah dana konsumen masuk). Opsi lain adalah menggunakan mekanisme split-payment langsung di tingkat *Payment Gateway* (saat QRIS dipindai, 90% otomatis pecah ke rekening merchant, 10% otomatis pecah ke rekening penampung pajak).

### B. Risiko Resistensi Wajib Pajak (Kepatuhan Mandiri)

* **Risiko:** Karena "Kalkulator Bisnis" bersifat aplikasi tempat *input* manual, pemilik usaha bisa saja mengakali sistem dengan tidak memasukkan transaksi konsumen ke kalkulator tersebut (transaksi bawah meja). Berbeda dengan *Tapping Box* yang menangkap data langsung dari mesin kasir (POS).
* **Mitigasi:** Kalkulator Bisnis di M-PAD harus diintegrasikan lewat API dengan sistem POS/Kasir digital yang digunakan merchant (misal Moka, Majoo, dll.), atau Pemda tetap menjalankan **Uji Petik Digital** secara berkala (inspeksi mendadak mencocokkan jumlah pengunjung dengan transaksi yang di-input).

### C. Risiko Beban Biaya Transaksi (Biaya H2H/AFT)

* **Risiko:** Setiap transaksi *bridging/AFT* antar bank sering kali memicu biaya administrasi (skema BI-FAST atau kliring). Jika nilai transaksi pajak kecil (misal pajak bakso Rp2.000) namun biaya AFT-nya Rp2.500, maka sistem akan merugikan keuangan daerah atau wajib pajak.
* **Mitigasi:** Pemda harus membuat Perjanjian Kerja Sama khusus dengan Bank BPD (Bank Sultra) agar seluruh transaksi AFT dari rekening merchant ke rekening penampung pajak dikenakan **biaya Rp0,- (bebas biaya administrasi)** sebagai bentuk dukungan program ETPD.

---

### Kesimpulan Cetak Biru untuk Sultra Connect

Skenario **Kalkulator Bisnis + AFT** ini sangat layak diterapkan dan akan menjadi inovasi pionir. Skema ini sangat cocok ditarik ke skala **Sultra Connect**, karena menghemat miliaran rupiah APBD yang biasanya habis untuk membeli alat fisik *Tapping Box* untuk ribuan merchant di 17 kabupaten/kota. Kunci utamanya berada pada **Aspek Legal (Surat Kuasa Debet)** dan **Kesiapan API Bank BPD** untuk melakukan eksekusi *Split-Payment/AFT* secara instan.

Ide menggantikan *Tapping Box* fisik dengan **Kalkulator Bisnis Digital** yang terintegrasi langsung dengan mekanisme *Auto-Debit* atau *Automatic Fund Transfer* (AFT) dari rekening pemilik usaha ke rekening penampung pajak adalah inovasi yang sangat progresif.

Skenario ini memindahkan instrumen pengawasan dari **perangkat keras** (*hardware-based monitoring*) ke **aliran dana perbankan** (*cashflow-based enforcement*).

Berikut adalah rancangan alur kerja, analisis regulasi, serta risiko teknis dan hukum atas skenario tersebut:

---

## 1. Alur Kerja (Workflow) Sub-Menu Kalkulator Bisnis & AFT

Dalam sub-menu Wajib Pajak (Pemungut/Merchant), proses kerja digital dirancang sebagai berikut:

* **Langkah 1: Input & Perhitungan (Kalkulator Bisnis)**
Saat konsumen membayar, kasir/pemilik usaha memasukkan nilai transaksi ke fitur "Kalkulator Bisnis" di aplikasi M-PAD. Sistem otomatis memisahkan komponen Pendapatan Usaha dan komponen Pajak Barang dan Jasa Tertentu (PBJT, misal 10%).
* **Langkah 2: Pembayaran Konsumen & Rekening Utama**
Konsumen membayar total tagihan melalui QRIS dinamis atau transfer bank yang disediakan sistem. 100% dana masuk terlebih dahulu ke **Rekening Operasional Pemilik Usaha** (untuk menjaga hak likuiditas harian merchant).
* **Langkah 3: Trigger Transaksi & Mekanisme AFT**
Begitu dana masuk ke rekening pemilik usaha, sistem M-PAD yang terhubung secara H2H dengan Bank BPD (Bank Sultra) mendeteksi transaksi sukses tersebut. Sistem langsung memicu perintah **AFT (Automatic Fund Transfer)** untuk memotong *hanya* komponen pajak (10%) dari rekening pemilik usaha.
* **Langkah 4: Rekening Penampungan & Settlement**
Dana hasil potong otomatis (AFT) tersebut masuk ke **Rekening Penampungan Sementara (Escrow Account) Pajak** di Bank BPD, sebelum akhirnya disetor secara massal ke Kas Daerah (Kasda) pada akhir hari atau periode tertentu secara otomatis (e-Resi terbit seketika).

---

## 2. Analisis Regulasi: Apakah Menyalahi Perundang-Undangan?

Secara prinsip dasar perpajakan, skenario ini **TIDAK menyalahi undang-undang, bahkan sangat sejalan** dengan semangat UU No. 1 Tahun 2022 (UU HKPD) dan Perpres No. 95 Tahun 2018 tentang SPBE (Sistem Pemerintahan Berbasis Elektronik). Namun, ada batasan hukum yang wajib dijaga ketat:

### Yang LEGAL dan Mendukung Undang-Undang:

* **Asas Titipan (Fidusia Pajak):** Pemilik usaha di sini bertindak sebagai **Pemungut Pajak** (*Withholding Tax*). Uang pajak yang dibayar konsumen pada hakikatnya bukan milik pengusaha, melainkan milik daerah. Menarik uang tersebut secara otomatis justru mempercepat penyerahan hak negara.
* **Elektronifikasi Transaksi Pemda (ETPD):** Satgas Nasional ETPD sangat mendorong inovasi digital yang mempercepat integrasi sistem keuangan daerah dengan bank persepsi untuk mencegah kebocoran anggaran (*leakage*).

### Yang Berpotensi Melanggar (Jika Tidak Diantisipasi):

* **Hak Privasi Perbankan (UU Perbankan):** Bank tidak boleh memotong atau memindahkan dana dari rekening nasabah tanpa persetujuan tertulis. Jika M-PAD melakukan AFT tanpa dasar, ini menyalahi aturan kerahasiaan bank.
* **Solusi Hukum:** Harus ada **Surat Kuasa Debet Rekening / Perjanjian Kerja Sama (PKS)** tertulis bertandatangan elektronik antara Pemilik Usaha, Pemda (Bapenda), dan Bank BPD saat pendaftaran akun M-PAD. Pengusaha secara sadar memberikan kuasa kepada sistem untuk melakukan AFT atas komponen pajak.

---

## 3. Risiko Atas Skenario Ini & Mitigasinya

Meskipun sistem ini efisien karena memangkas biaya pengadaan alat *Tapping Box*, terdapat risiko operasional dan teknis yang tinggi:

### A. Risiko Likuiditas dan *Gagal Debet* (Timing Risk)

* **Risiko:** Jika jeda waktu antara masuknya uang konsumen ke rekening pemilik usaha dengan eksekusi AFT terlalu lama, ada risiko uang tersebut langsung digunakan pemilik usaha untuk operasional lain (misal membayar *supplier*), sehingga saat sistem melakukan AFT, saldo di rekening pengusaha sudah tidak mencukupi (gagal debet).
* **Mitigasi:** Eksekusi AFT harus bersifat *real-time* atau *near-instant* (hitungan detik setelah dana konsumen masuk). Opsi lain adalah menggunakan mekanisme split-payment langsung di tingkat *Payment Gateway* (saat QRIS dipindai, 90% otomatis pecah ke rekening merchant, 10% otomatis pecah ke rekening penampung pajak).

### B. Risiko Resistensi Wajib Pajak (Kepatuhan Mandiri)

* **Risiko:** Karena "Kalkulator Bisnis" bersifat aplikasi tempat *input* manual, pemilik usaha bisa saja mengakali sistem dengan tidak memasukkan transaksi konsumen ke kalkulator tersebut (transaksi bawah meja). Berbeda dengan *Tapping Box* yang menangkap data langsung dari mesin kasir (POS).
* **Mitigasi:** Kalkulator Bisnis di M-PAD harus diintegrasikan lewat API dengan sistem POS/Kasir digital yang digunakan merchant (misal Moka, Majoo, dll.), atau Pemda tetap menjalankan **Uji Petik Digital** secara berkala (inspeksi mendadak mencocokkan jumlah pengunjung dengan transaksi yang di-input).

### C. Risiko Beban Biaya Transaksi (Biaya H2H/AFT)

* **Risiko:** Setiap transaksi *bridging/AFT* antar bank sering kali memicu biaya administrasi (skema BI-FAST atau kliring). Jika nilai transaksi pajak kecil (misal pajak bakso Rp2.000) namun biaya AFT-nya Rp2.500, maka sistem akan merugikan keuangan daerah atau wajib pajak.
* **Mitigasi:** Pemda harus membuat Perjanjian Kerja Sama khusus dengan Bank BPD (Bank Sultra) agar seluruh transaksi AFT dari rekening merchant ke rekening penampung pajak dikenakan **biaya Rp0,- (bebas biaya administrasi)** sebagai bentuk dukungan program ETPD.

---

### Kesimpulan Cetak Biru untuk Sultra Connect

Skenario **Kalkulator Bisnis + AFT** ini sangat layak diterapkan dan akan menjadi inovasi pionir. Skema ini sangat cocok ditarik ke skala **Sultra Connect**, karena menghemat miliaran rupiah APBD yang biasanya habis untuk membeli alat fisik *Tapping Box* untuk ribuan merchant di 17 kabupaten/kota. Kunci utamanya berada pada **Aspek Legal (Surat Kuasa Debet)** dan **Kesiapan API Bank BPD** untuk melakukan eksekusi *Split-Payment/AFT* secara instan.