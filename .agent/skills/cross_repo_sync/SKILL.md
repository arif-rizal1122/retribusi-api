---
name: "Cross-Repo Feature Synchronization (M-PAD Master Pipeline)"
description: "Panduan standar untuk mensinkronisasi penambahan atau perubahan fitur di seluruh ekosistem M-PAD (retribusi-api, retribusi-mobile, retribusi-petugas, retribusi-admin) untuk menjaga konsistensi tipe data, kontrak API, dan peran pengguna."
---

# Cross-Repo Feature Synchronization (M-PAD Ecosystem)

## 1. Arsitektur 4 Pilar M-PAD

Ekosistem Pendapatan Asli Daerah (M-PAD) Kota Baubau terdiri dari 4 repositori inti yang saling terikat:

| Repositori | Peran & Tanggung Jawab | Consumer / User |
| :--- | :--- | :--- |
| **`retribusi-api`** | Sumber kebenaran tunggal (Database, Model, Controller, Formula Perda 1/2024, Snap Gateway, TTE) | Backend Core |
| **`retribusi-mobile`** | Aplikasi Warga (Pendaftaran WP/NOP, Pembayaran QRIS/VA, Lapor SPTPD, Pelacakan) | Wajib Pajak / Masyarakat |
| **`retribusi-petugas`** | Aplikasi Lapangan (QR Scanner, Bayar Tunai & Cetak Resi Thermal, Survei Bangunan, Peta Lapangan) | Petugas Bapenda / Lapangan |
| **`retribusi-admin`** | Dashboard Manajemen (Master Data, Penetapan SKPD/SPPT, Rekonsiliasi Bank, TTE, Laporan SIPD) | Pejabat OPD / Superadmin |

---

## 2. Alur Sinkronisasi Fitur (Feature Rollout Workflow)

Setiap pengembangan fitur baru yang melintasi repositori wajib mengikuti 5 langkah berurutan:

```
[ Step 1: API Contract ] ──► [ Step 2: Mobile UI ] ──► [ Step 3: Petugas Flow ] ──► [ Step 4: Admin Management ] ──► [ Step 5: E2E Quality Gate ]
```

### Langkah 1: API Contract & Schema (`retribusi-api`)
1. Buat migration database yang reversible jika ada kolom/tabel baru.
2. Definisikan model, relasi, cast, dan validasi request di `FormRequest` / `Controller`.
3. Tulis route di `routes/api.php` di bawah middleware auth / role yang sesuai (`citizen`, `petugas`, `admin`).
4. Pastikan response JSON terstandarisasi `{ success: true, data: ..., message: ... }`.

### Langkah 2: Citizen Flow (`retribusi-mobile`)
1. Sinkronkan interface / types TypeScript di `src/services/...Service.ts`.
2. Terapkan prinsip **Citizen-Centric Design** (lihat `riset_blanko_opd` skill):
   - Jangan menampilkan field teknis yang warga tidak pahami.
   - Gunakan auto-fill IKD dan `MapPicker` untuk GIS.
   - Sediakan tombol bayar (BRIVA / QRIS / Transfer / Petugas) dan receipt download.

### Langkah 3: Field Operations (`retribusi-petugas`)
1. Implementasikan scanner QR atau quick lookup (NIK / NPWPD / Bill Number).
2. Tambahkan aksi lapangan: verifikasi data, pencatatan tunai, dan cetak struk via `ThermalPrintService.ts`.
3. Pastikan layout responsif dan kompatibel untuk PWA / Android tablet.

### Langkah 4: Administrative Control (`retribusi-admin`)
1. Tambahkan menu/tabel review di admin dashboard.
2. Sediakan visualisasi data spasial (titik/poligon) dan viewer dokumen PDF/gambar.
3. Sediakan tombol aksi approval / penolakan dengan catatan feedback.
4. Hubungkan ke modul TTE jika dokumen membutuhkan pengesahan pejabat.

### Langkah 5: Verification & Quality Gate
Jalankan di seluruh repositori yang tersentuh:
```bash
# 1. retribusi-api
php artisan test --filter=FeatureNameTest

# 2. retribusi-mobile
cd ../retribusi-mobile && npm run typecheck && npm run build

# 3. retribusi-petugas
cd ../retribusi-petugas && npm run typecheck && npm run build

# 4. retribusi-admin
cd ../retribusi-admin && npm run typecheck && npm run build
```

---

## 3. Matriks Pencegahan Regresi (Anti-Regression Rules)

1. **No Mocking in Production**: Dilarang menggunakan mock/hardcoded response jika API sesungguhnya sudah tersedia.
2. **Idempotent Payments**: Pembayaran tidak boleh menghasilkan double payment; selalu gunakan transaction lock di API.
3. **Receipt Parity**: Nomor resi dan NTPD yang dicetak di thermal printer petugas harus identik dengan SSPD yang diunduh warga di mobile dan admin.
