# Progres Integrasi Open API Bank BTN

Dokumen ini mencatat progres tahapan administratif dan teknis integrasi sistem mPAD Pemkot Baubau dengan Open API Bank BTN (H2H), berdasarkan log komunikasi dengan pihak Bank BTN.

## Timeline Progres

### 8 Mei 2026
- **Inisiasi Dokumen**: Permintaan formulir API, draft NDA (Non-Disclosure Agreement), dan PKS (Perjanjian Kerja Sama).
- **Tindak Lanjut**: Pihak BTN telah mengirimkan dokumen-dokumen persyaratan untuk kelengkapan layanan OPEN API untuk dilengkapi oleh Bapenda.

### 11 Mei 2026
- **Pengembalian Draft PKS**: Pihak Bapenda telah menyusun dan mengirimkan Draft PKS kepada BTN (dengan bagian yang di-_highlight_ merah untuk diisi oleh pihak bank).

### 25 Juni 2026
- **Penyetoran Data PIC**: Pihak bank meminta kelengkapan data *Person in Charge* (PIC) untuk keperluan pengelola _user_ dan _password_.
- **Tindak Lanjut**: Bapenda menyerahkan data sebagai berikut:
  - Kepala Bapenda: Muhammad Massad, S.E., M.Si.
  - PIC 1 (Internal Bapenda/Kabid Wil-1): Fauziah, SP., M.Si.
  - PIC 2 (Arsitektur System/Pengembang): Muhdan Fyan Syah Sofian
  - Email Resmi Bapenda: `bapendakotabaubau21@gmail.com`

### 1 Juli 2026
- **Permintaan Kredensial UAT**: Koordinator IT Bapenda meminta pihak BTN memfasilitasi Kredensial Environment UAT/DEV (Client ID, Secret Key, Base URL) karena sistem sudah mendukung TLS 1.3 dan m-PAD akan segera *launching*.
- **Kendala (Bottleneck) Administratif**: Pihak BTN mengonfirmasi bahwa penyerahan kredensial (Key) baru dapat dilakukan setelah dokumen fisik **NDA dan Formulir API ditandatangani oleh manajemen BTN**. Saat itu, NDA sudah di-ttd, namun fisik Formulir API masih menunggu tanda tangan.

### 13 Juli 2026 (Update Terkini)
- **Clearance Administratif**: BTN mengonfirmasi bahwa dokumen fisik **NDA dan Formulir API telah ditandatangani sepenuhnya** oleh BTN dan Bapenda Baubau. Status: **Siap untuk _development_**.
- **Arahan Pimpinan**: Kepala Bapenda (Pak Massad) menginstruksikan tim pengembang untuk segera masuk ke tahap percepatan *development*.
- **Penyerahan Dokumen API Spesifikasi**: Pihak BTN meminta email *corporate* untuk mengirimkan dokumen spesifikasi API. Bapenda telah memberikan alamat email: `bapenda@baubaukota.go.id`.

---

## Action Items (Pending / Next Steps)

1. [ ] **Bank BTN**: Mengirimkan Dokumen Spesifikasi API ke email `bapenda@baubaukota.go.id`.
2. [ ] **Bank BTN**: Menyerahkan Kredensial UAT/DEV (Client ID, Secret Key, Base URL, dll) karena persyaratan administratif telah selesai.
3. [ ] **Tim Pengembang (Bapenda)**: Mempelajari dokumen spesifikasi API dari BTN dan melakukan setup _environment_ UAT berdasarkan kredensial yang diberikan.
4. [ ] **Tim Pengembang (Bapenda)**: Memulai *development* integrasi teknis dan pengujian (testing) konektivitas API.
