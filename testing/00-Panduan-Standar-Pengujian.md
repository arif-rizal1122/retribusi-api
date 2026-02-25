# 00 - Panduan Standar Skema Pengujian Terpadu (Master Guideline)

Dokumen ini adalah **Acuan Utama (Master Schema)** untuk seluruh kegiatan pengujian (Testing) pada aplikasi (MITRA: Admin, Petugas, Mobile, dan API Backend). 

Setiap fitur yang dirilis, termasuk namun tidak terbatas pada Alur Utama E2E, Keamanan RBAC, Validasi Data, hingga Kalkulasi Formula Pajak wajib mengacu pada 5 Lapisan Pengujian di bawah ini. Jika sebuah dokumen pengujian lain di folder ini memiliki pengecekan fungsi yang beririsan, maka instruksi pemeringkatan tes di sini yang menjadi pemegang kedali standar.

> **CATATAN KRITIKAL (/noss):** 
> Seluruh proses pengetesan pada skema ini dan modul-modul turunannya dideklarasikan **TIDAK MENGGUNAKAN SCREENSHOT** ataupun tangkapan layar. Validasi keberhasilan harus murni dibuktikan dari pembacaan CLI/Terminal, JSON Response dari Postman/Insomnia, Assertions Kode, dan data Database secara real-time.

---

## Lapisan Skema Pengujian (Testing Layers)

### 1. Unit Testing & Feature Testing
**Fokus Utama:** Menguji fungsi/metode individual (Unit) dan logika relasi model database (Feature) langsung di level *source code* backend tanpa menjalankan full HTTP request.
* **Standar Skenario:**
  - Mocking objek `User`, Objek Pajak, atau instance lainnya yang dibutuhkan sesuai konteks pengujian.
  - Memanggil model atau *service locator* secara langsung (contoh: `app(\App\Services\FormulaParserService::class)->calculate()`).
* **Standar Validitas (No Screenshot):** 
  - *PHPUnit / Pest Assertion* me-return respon `true` atau `assertEquals`.
  - Logika struktur Query Database melalui *toSql()* atau logging DB mengeksekusi parameter `where` yang presisi.

### 2. API Testing
**Fokus Utama:** Menguji titik akhir (Endpoint/Routes) dari segi validitas *Request Payload* dan *Response Payload*, serta status kode HTTP dari koneksi client-server.
* **Standar Skenario:**
  - Menghantam URL target di `routes/api.php` dengan *Bearer Token* Auth palsu atau asli sesuai peran.
  - Memodifikasi variasi header, *query params*, atau Form Data.
* **Standar Validitas (No Screenshot):** 
  - Konsol inspektor mengembalikan eksekusi *Status Code* yang relevan (`200 OK`, `201 Created`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Content`, dll).
  - Skema struktur array JSON *Response* (`data`, `message`, `meta`) sesuai dengan cetak biru yang diharapkan tanpa ada relasi asing yang terekspos (*data bocor*).

### 3. CRUD Testing
**Fokus Utama:** Menguji alur siklus hidup utama (Create, Read, Update, Delete) terkait keutuhan dan persistensi data di tabel Database. Lapisan ini memastikan fungsi form dan rekayasa manipulasi database berjalan sempurna.
* **Standar Skenario:**
  - Permintaan penambahan baris (Create) dengan parameter wajib vs kosong.
  - Permintaan modifikasi kolom spesifik (Update) parsial atau penuh.
  - Permintaan penghapusan rekaman (Delete) dengan pengujian Soft-Delete maupun Hard-Delete (jika terkait).
* **Standar Validitas (No Screenshot):**
  - Database terminal SQL *Client* / Laravel Tinker membuktikan bahwa data sukses tertaut (count baris bertambah/berkurang).
  - Validasi penolakan sistemik pada operasi ilegal yang tidak dimandatkan pada user tersebut ter-blokir dengan sempurna di level Eloquent atau *Form Request*.

### 4. UI Testing (Headless / E2E Automation)
**Fokus Utama:** Menguji elemen antarmuka DOM (*Document Object Model*) menggunakan simulasi aktivitas browser *user* tiruan seperti penekanan tombol, input teks, dan navigasi Router. Pengecekan tidak lagi melalui tatapan mata QA manual, melainkan interaksi sintaks mesin.
* **Standar Skenario:**
  - Menjalankan Automation Scripting (mis. dengan Cypress, Laravel Dusk, atau Playwright).
  - Mengarahkan mesin menuju alamat laman contoh `/taxpayers`. 
  - Menginstruksikan bot mengetik sesuatu di kotak `#search` dan menekan `.btn-submit`.
* **Standar Validitas (No Screenshot):**
  - Terciptanya jejak hijau ceklis di Terminal OS (misalnya *Expected <table-row> to have length 5 -> Passed!*).
  - Eksekusi *Document.querySelector* menemukan *class* dan nilai teks (innerText) yang selongsong dengan perhitungan mesin, bukan gambar statis.

### 5. Integration / End-to-End System Testing
**Fokus Utama:** Mengkonfirmasi bahwa komunikasi berbagai *services*, *scheduler* (cron), pihak eksternal, dan siklus transaksi lintas aplikasi beroperasi mengalir tanpa hambatan fungsional.
* **Standar Skenario:**
  - Kombinasi registrasi dari aplikasi Mobile -> Pengesahan di Admin Web -> Pengecekan titik di Map Petugas.
* **Standar Validitas (No Screenshot):**
  - Transmisi data lintas platform tidak menghasilkan *Delay/Failure*. Verifikasi terekam dalam *System Logs* pusat atau integrasi webhook yang diterima valid.

---
**Pemberlakuan Skema:**
Terhitung semenjak dokumen ini dirilis, *keseluruhan file markdown uji kasus* di folder direktori `testing/` (seperti *01 hingga 10*, dll) otomatis wajib mengacu secara fundamental kepada instruksi *5 Lapisan Skema Testing* dalam dokumen Acuan Utama bernomor `00` ini. Fungsi pengecekan logika bisnis yang serupa dari berbagai file diintegrasikan ke standar kelulusan ini.
