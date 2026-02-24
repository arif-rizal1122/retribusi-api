# Tahap 5: Pengujian Perangkat Layar Real (Cross-Device UAT)

Aplikasi canggih tiada guna bila hancur antarmukanya saat dipakai Pejabat/Warga dengan beragam jenis ukuran monitor dan tipe peramban gawai di lapangan nyata.

## 5.1. Kompatibilitas Browser Komputer (PC/Laptop)
- **Langkah Pengujian Modal CSS**:
  - Gunakan 2 Browser di luar zona nyaman Chrome (misal **Mozilla Firefox** Mac/Windows dan **Safari** / **Microsoft Edge**).
  - Masuk ke *Web Admin* (`admin.sipanda.online`).
  - Buka formulir berat (seperti Tambah Objek Pajak, atau Manajemen Pengguna UserManagement/Roles) yang membutuhkan Pop-up Dialog *Modal Container* bertindih.
  - **Kriteria Valid**: Layar *Modal* formulir pendaftaran tersebut tidak boleh cacat. Kaki tabel atau baris *Submit* penutup tidak boleh *Cut-off* (memotong elemen di luar *overlay* akibat *overflow hidden* bug). Modal bersifat lentur bisa di-scroll sampai bawah.

## 5.2. Layar HP Ujung Tanduk (Responsive Extremes)
- **Langkah Pengujian Aplikasi Kolektor (`petugas.sipanda.online`)**:
  - Pinjam dua jenis perangkat bertolak belakang:
    1.  Tabulatur Layar Sangat Besar/Horizontal (Tablet Windows / Layar >6.5 inch).
    2.  Ponsel Kuno berlayar sipit vertikal (*iPhone SE Generasi 1*, Android mini ukuran 4 inci).
  - Sebagai petugas pemungut di jalan, coba login dan masukan baris nomor NIK pencarian tagihan.
  - Saat Tagihan keluar dan melengkapi form Setoran manual.
  - **Kriteria Valid**: Meskipun jari petugas agak tebal/layarnya kurus memanjang, ukuran tombol "Konfirmasi Pembayaran Lunas" dan teks *Input TextBox* masih bisa disentuh telunjuk empuk (Padding/Margin ramah *Tap Target Size* Standard A11Y). Layar bergulir horizontal stabil (Responsif Grid Column pecah dari _desktop table_ ke _Card layout_ Mobile).
