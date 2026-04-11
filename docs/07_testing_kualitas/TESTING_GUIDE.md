# 🧪 Panduan Pengujian (Testing Guide) - Baubau 2024

Dokumen ini adalah acuan untuk menjalankan pengujian sistem M-PAD dengan hirarki **Wilayah-Centric**.

## 1. Fokus Pengujian Baru
Seiring dengan perubahan struktur ke Wilayah I & II, pengujian wajib mencakup:
1.  **Isolasi Wilayah**: Memastikan Petugas Wilayah I tidak dapat mengakses/menagih Objek di Wilayah II.
2.  **Akurasi Klasifikasi (Level 2)**: Verifikasi bahwa rumus dan ikon diambil dari tabel `retribution_classifications`.
3.  **Spatial-Tariff (Level 3 & 4)**: Verifikasi bahwa pemilihan Zona (misal: Kelas Jalan A) menghasilkan tarif Rupiah yang tepat.

## 2. Akun & Parameter Acuan
| Unit | Parameter Test |
| :--- | :--- |
| **Wilayah I (ID 16)** | Wolio, Murhum, Betoambari, Batupoaro |
| **Wilayah II (ID 17)** | Kokalukuna, Sorawolio, Lea-Lea, Bungi |
| **Password Standard** | `password123` |

## 3. Skenario "Golden Path" (End-to-End)
Untuk memverifikasi integrasi penuh, jalankan skenario berikut:
1.  **Pendaftaran**: Gunakan `SPOPD` untuk mendaftarkan objek baru di Kecamatan `Wolio`.
2.  **Penetapan**: Pastikan Parent ID otomatis terisi `16` (Wilayah I).
3.  **Zonasi**: Pilih `Zona Premium` dan pastikan tarif parkir muncul sebagai `Rp 3.000`.
4.  **Penagihan**: Generate `SKRD` dan verifikasi QR-Code TTE muncul.

## 4. Instruksi AI Agent
Gunakan skill spesialis untuk akurasi maksimal:
- **Protokol E2E**: Aktifkan skill `omni_workspace_tester`.
- **Audit Regulasi**: Verifikasi hasil hitung terhadap [Master Regulasi](file:///Users/pondokit/Herd/retribusi-api/docs/01_regulasi_baubau/BAUBAU_REGULATORY_MASTER.md).

