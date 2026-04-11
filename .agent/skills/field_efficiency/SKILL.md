---
name: Field Service Optimization (QR & Logic)
description: Panduan peningkatan efisiensi kerja petugas lapangan melalui pemanfaatan teknologi QR Scanner dan logika otomasi UI.
---

# ⚡ Field Service Optimization (QR & Logic)

Skill ini berfokus pada minimalisir "Human Interaction" saat petugas bekerja di lapangan, memastikan setiap detik layanan berharga dan akurat.

## 📱 1. Smart QR Redirection (Context-Aware)
Petugas tidak perlu memilih menu secara manual. Sistem harus mampu mendeteksi jenis dokumen dari *payload* QR Code:
- **Alur**:
  - Scan QR -> Deteksi Format -> Redirect ke Halaman yang Tepat.
- **Implementasi**: 
  - Gunakan `QRScannerService` (Petugas App).
  - Jika QR berasal dari **SPOP/LSPOP** (PBB) -> Lari ke pendaftaran PBB.
  - Jika QR berasal dari **SPOPD** (Usaha) -> Lari ke pendaftaran objek pajak (PBJT/Reklame).
  - Jika QR berisi `bill_number` (SKRD) -> Lari ke `/billing`.

## 📝 2. Digital LKOK (Field Evidence)
Setiap kunjungan lapangan wajib menghasilkan **LKOK (Lembar Kerja Objek Khusus)**.
- **Workflow**: Scan Objek -> Ambil Foto -> Isi Temuan Lapangan -> Generate LKOK Digital.
- **TTE**: LKOK yang sudah final dapat langsung di-TTE oleh petugas sebagai bukti otentik pendataan.
- **Auto-Inquiry**: Begitu QR terbaca, sistem langsung melakukan API call `inquiry` tanpa menunggu tombol "Cari" diklik.
- **Auto-Payment Modal**: Jika inquiry berhasil dan data unik ditemukan, buka modal pembayaran secara otomatis.
- **Result**: Target efisiensi adalah menyelesaikan 1 transaksi dalam kurun waktu kurang dari 15 detik.

## 🗺️ 3. Geofencing & Task Priority
Memaksimalkan rute perjalanan petugas:
- **Task Sorting**: Urutkan daftar tugas berdasarkan `distance` (jarak terdekat) dari lokasi GPS petugas saat ini.
- **Geofence Check**: Hanya izinkan tombol "Bayar" aktif jika koordinat GPS petugas berada dalam radius < 100 meter dari koordinat `TaxObject`.

## 🛠️ Komponen Teknis
- **Services**: `QRScannerService.ts`.
- **Pages**: `FieldScanner.tsx`, `Billing.tsx`.
- **Logic**: Manfaatkan `useEffect` untuk mendengarkan perubahan hasil scan dan memicu aksi modal secara otomatis.

> [!TIP]
> Selalu lakukan "Dry Run" pemindaian menggunakan berbagai variasi QR (PBB, SKRD, Tagihan Mandiri) setelah melakukan update pada `QRScannerService` untuk memastikan tidak ada rute yang salah (Dead end).
