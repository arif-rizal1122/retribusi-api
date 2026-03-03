# Consolidated Todolist - March 2, 2026

## 🔄 Merged from Previous Session (Tadi Siang)
- [x] **Dashboard & Laporan**
  - [x] API: Granular Achievement Stats
  - [x] API: Taxpayer Payment History
  - [x] Dashboard: Achievement Per Object UI
  - [x] Detail WP: Riwayat Pembayaran UI
  - [ ] Reporting kebutuhan BPK (ID: 12)
- [x] **SKPDLB / SKRDLB (Lebih/Kurang Bayar)**
- [ ] **Penghapusan Denda (DEFERRED)**
  - [ ] Mekanisme pengajuan & persetujuan (ID: 28, 29)
  - [ ] Generate surat keputusan penghapusan denda (ID: 30)
  - > [!NOTE]
  > Berkaitan dengan denda keterlambatan. Skema pengajuan disiapkan untuk kasus kesalahan sistem atau terlambat bayar karena hari libur.
- [ ] **Verifikasi & Audit**
  - [ ] Perbaikan skema verifikasi (Preview before/after) (ID: 37)
  - [ ] Wajib isi alasan/catatan (ID: 38)
  - [ ] Audit log lengkap & Visual diff (ID: 41, 42)

## 🏗️ Future Architecture (New Concept)
- [ ] **Arsitektur Hybrid Dynamic Billing (DEFERRED - Simpan untuk Setelah Pelatihan)**
  - [ ] Refactor `BillingService` untuk deteksi tunggakan real-time (Virtual Discovery).
  - [ ] Implementasi **Just-in-Time (JIT) Billing** (Auto-generate record `bills` saat aksi bayar).
  - [ ] Dashboard ketaatan pajak berbasis real-time data pendaftaran vs pembayaran.

## 📖 Current Documentation Tasks
- [ ] **User Guide Petugas (URGENT)**
  - [ ] Jalankan testing mandiri di `petugas.online`
  - [ ] Capture screenshot login -> selesai
  - [ ] Update `userguide.md` dengan gambar Cloudinary
- [ ] **Admin Guide**
  - [ ] Teruskan pengerjaan fase-fase yang tersisa di `TASK_CONTEXT.md`

## ✅ Completed Today
- [x] Secured Repository Context (Migrated to `docs/`)
- [x] Verified Stable Baseline Commit IDs
- [x] Input 4 petugas users to VPS database
