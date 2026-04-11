# Rencana Integrasi PBB (Pajak Bumi dan Bangunan) - Bapenda Baubau

## 1. Strategi Integrasi: Proxy Langsung & Klaim NOP

### Konsep Utama
*   **Proxy Langsung**: Aplikasi hanya menjadi "jembatan" ke API Bapenda untuk pengecekan tagihan dan pembayaran. Data objek pajak PBB tidak disimpan/disalin ke database lokal untuk menghindari sinkronisasi yang rumit dan data usang.
*   **Klaim NOP (Link NOP)**: Mengingat API Bapenda tidak memiliki data NIK, User (Wajib Pajak) yang sudah memiliki akun di aplikasi Retribusi (berbasis NIK) harus melakukan **"Klaim NOP"** sekali saja. Setelah itu, sistem akan menyimpan hubungan antara `user_id` dan `nop`.

### Komponen Sistem

#### A. Database (Schema Changes)
Kita tidak mengubah struktur tabel inti (`payments`, `bills`, `tax_objects`) yang sudah ada. Kita menambahkan tabel pelengkap ("sidecar tables"):

1.  **`taxpayer_pbb_objects`** (Menyimpan daftar NOP milik user)
    ```sql
    CREATE TABLE taxpayer_pbb_objects (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        taxpayer_id BIGINT UNSIGNED NOT NULL, -- Relasi ke tabel 'taxpayers' existing
        nop VARCHAR(25) NOT NULL,             -- NOP Pajak
        name_on_sppt VARCHAR(255),            -- Nama di SPPT (untuk validasi visual)
        address_on_sppt VARCHAR(255),         -- Alamat di SPPT
        is_verified BOOLEAN DEFAULT FALSE,    
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL,
        
        FOREIGN KEY (taxpayer_id) REFERENCES taxpayers(id) ON DELETE CASCADE,
        UNIQUE KEY (taxpayer_id, nop)
    );
    ```

2.  **`transaction_pbb`** (Mencatat riwayat transaksi pembayaran PBB)
    ```sql
    CREATE TABLE transaction_pbb (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NULL,          -- User yang melakukan transaksi (bisa petugas/warga)
        nop VARCHAR(20) NOT NULL,
        tahun VARCHAR(4) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        denda DECIMAL(15,2) DEFAULT 0,
        total_bayar DECIMAL(15,2) NOT NULL,
        ntpd VARCHAR(50) NULL,        -- Bukti sah dari Bapenda
        payment_status ENUM('pending', 'success', 'failed', 'reversed') DEFAULT 'pending',
        
        -- Snapshot data WP saat bayar
        wp_name VARCHAR(100),
        wp_address VARCHAR(200),
        
        api_response_json TEXT,       -- Log respon lengkap untuk audit
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL
    );
    ```

#### B. API Service (`retribusi-api`)
1.  **`PbbService` Class**:
    *   `login()`: Mendapatkan Token Bearer, cache support (Redis/File) 24 jam.
    *   `inquiry(nop, tahun)`: Cek tagihan ke Bapenda.
    *   `payment(nop, tahun)`: Eksekusi pembayaran.
    *   `reversal(nop, tahun)`: Pembatalan (Admin only).
2.  **Endpoints**:
    *   `POST /api/pbb/link-nop`: User mengklaim NOP.
    *   `GET /api/pbb/my-objects`: List NOP user beserta tagihan *real-time*.
    *   `POST /api/pbb/pay`: Melakukan pembayaran.

#### C. Aplikasi Mobile (`retribusi-mobile`)
*   **Menu PBB**:
    *   Input NOP baru -> Validasi Nama -> Simpan.
    *   List Tagihan PBB saya (dari NOP yang sudah disimpan).
    *   Bayar via Saldo/QRIS (sistem internal retribusi) -> Backend tembak API Bapenda.

#### D. Aplikasi Petugas (`retribusi-petugas`)
*   Menu **"Bayar PBB Warga"**:
    *   Petugas input NOP warga.
    *   Petugas terima uang tunai.
    *   Petugas klik "Bayar" -> Dapat NTPD -> Cetak Struk Bluetooth.

---

## 2. Analisis Kalkulator PBB & Klasifikasi NJOP

### Kondisi Saat Ini
Di database lokal sudah ada tabel `pbb_njop_classifications` yang berisi klasifikasi nilai tanah/bangunan.

### Rekomendasi Integrasi Kalkulator
Untuk fitur **"Kalkulator Simulasi PBB"** (hanya simulasi, bukan tagihan resmi), kita bisa menggunakan data klasifikasi ini.

**Apakah kalkulasinya sesuai?**
*   **API Bapenda**: Mengembalikan nilai `TOTAL_HARUS_DIBAYAR` yang sudah final (sudah dihitung server Bapenda). **Ini yang kita pakai untuk pembayaran.**
*   **Kalkulator Lokal**: Hanya untuk simulasi "Berapa kira-kira pajak saya jika luas tanah sekian?".
    *   Kita **TIDAK PERLU** menghitung ulang tagihan resmi menggunakan tabel klasifikasi lokal, karena itu berisiko selisih dengan data Bapenda.
    *   Gunakan tabel `pbb_njop_classifications` **HANYA** untuk fitur "Simulasi PBB" di aplikasi mobile, agar warga bisa memperkirakan pajak bangunan baru mereka.

**Kesimpulan Kalkulasi**:
*   Untuk **Pembayaran Resmi**: Gunakan angka dari API Bapenda (`inquiry`). JANGAN menghitung sendiri.
*   Untuk **Simulasi**: Gunakan tabel klasifikasi lokal.
