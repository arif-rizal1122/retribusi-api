# Skema Pengujian Master Data

Skema ini dirancang untuk memverifikasi fungsionalitas dan ketahanan input data pada 4 hirarki Master Data di lingkungan Local/Dev dan Production.

## 1. Pengujian Jenis (RetributionType)
| Skenario | Input Test | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| Input Valid | Nama Unik, Base Amount > 0 | Tersimpan dengan icon (jika ada) |
| Input Kosong | Nama: "", Base Amount: "" | Error 422: Validasi diperlukan |
| Karakter Khusus | Nama: "Jenis @!#$", Base Amount: 1000 | Tersimpan (sistem harus sanitize) |
| Upload Non-Image | File: "test.pdf" sebagai Icon | Error 422: Harus berupa file gambar |

## 2. Pengujian Klasifikasi (RetributionClassification)
| Skenario | Input Test | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| Relasi Valid | Pilih Jenis yang ada, Nama Baru | Tersimpan & Terkait ke Jenis tersebut |
| Rumus Kompleks | `(nsr * 0.25) + 5000` | Simulasi hitung berhasil (tidak 0) |
| Rumus Salah | `nsr * / 0.25` (Syntax Error) | Alert: Gagal menghitung (error logic) |
| Schema Kosong | Form Schema: [] | Berhasil (opsional untuk klasifikasi dasar) |

## 3. Pengujian Zona (Zone)
| Skenario | Input Test | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| Map Point | Klik di peta (Lat/Lng terisi) | Tersimpan sebagai geometri titik |
| Map Polygon | Gambar area (min 3 titik) | Tersimpan sebagai array koordinat |
| Code Duplikat | Gunakan kode yang sudah ada | Error 422: Kode sudah digunakan |
| Tanpa Lokasi | Simpan tanpa klik peta | Error/Default (tergantung kebutuhan bisnis) |

## 4. Pengujian Tarif (RetributionRate)
| Skenario | Input Test | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| Tarif Dasar | Klasifikasi A, Zona: All, Amount: 5000 | Tersimpan sebagai tarif standar |
| Tarif Zona | Klasifikasi A, Zona: Zona 1, Amount: 7500 | Tersimpan (tarif khusus area) |
| Amount Negatif | Amount: -100 | Error 422: Harus minimal 0 |
| Relasi Yatim | Hapus Klasifikasi sebelum Tarif | DB Constraint: Menolak hapus atau Cascade (tergantung FK) |

---

## Pemeriksaan Lingkungan (Local vs Production)
- **Local/Dev**: Cek logs via `tail -f storage/logs/laravel.log` saat simulasi.
- **Production**:
    - Monitor Network Tab (Status Code 500 vs 422).
    - Cek latensi upload Cloudinary.
    - Pastikan CORS tidak memblokir request API antar subdomain.

## 5. Simulasi Kesalahan (Error Checking)
1. **Uncaught TypeError**: Pastikan frontend menangani data null dari backend (terutama pada `details` atau `bank_accounts`).
2. **500 Internal Server Error (Kasus Khusus: Zona)**:
    - **Gejala**: Request ke `POST /api/zones` gagal dengan status 500.
    - **Kemungkinan Penyebab**:
        - Masalah mass-assignment pada model `Zone`.
        - Ketidakcocokan tipe data koordinat (JSON) di Database.
        - Foreign Key constraint yang gagal saat insert (misal `opd_id` atau `retribution_type_id`).
    - **Langkah Debugging**:
        - Cek `storage/logs/laravel.log` untuk exception detail.
        - Verifikasi `$fillable` di `app/Models/Zone.php`.
        - Gunakan `json_decode` jika data koordinat dikirim sebagai string mentah.
3. **Z-index/UI Bug**: Pastikan modal tidak tertutup overlay `react-leaflet`.
