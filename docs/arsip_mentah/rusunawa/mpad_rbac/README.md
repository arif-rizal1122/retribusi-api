# Arsitektur Tata Kelola Akses & Kinerja M-PAD (MITRA-PAD)
## Master Index: RBAC, Scoping Layanan, dan Pengukuran Kinerja

Repositori ini memuat cetak biru (*blueprint*), taksonomi, spesifikasi basis data, dan tata kelola otorisasi komprehensif untuk ekosistem **M-PAD (Mitra Pajak & Retribusi Daerah Kota Baubau)**.

---

### Daftar Dokumen Tata Kelola RBAC (`docs/mpad_rbac/`)

| No | Berkas Dokumen | Fokus & Isi Utama |
|---|---|---|
| **01** | [01_ARSITEKTUR_RBAC_HYBRID_MPAD.md](./01_ARSITEKTUR_RBAC_HYBRID_MPAD.md) | • Prinsip 3 Dimensi Otorisasi (Identity/Role, Tenant/OPD, Service & Territory)<br>• Taksonomi Role Multi-Platform (Web Admin, Mobile Petugas, Mobile Warga)<br>• Quadruple Security Layer (Route Guard, OPD Scope, Tupoksi Domain, Dynamic UI) |
| **02** | [02_PEMBATASAN_LAYANAN_DAN_WP_PETUGAS.md](./02_PEMBATASAN_LAYANAN_DAN_WP_PETUGAS.md) | • Analisis Kebutuhan Scoping Petugas (WP-Centric vs Object-Centric vs Territorial)<br>• Skema Relasi Database Penugasan (`officer_assignments` / `user_retribution_assignments`)<br>• Alur Delegasi Petugas oleh Admin OPD & Pencegahan Tumpang Tindih (*Zero Overlap*)<br>• Mekanisme Enforcing di Backend API & Mobile Petugas App |
| **03** | [03_RANTAI_ATRIBUSI_DAN_PENGUKURAN_KINERJA_PETUGAS_ADMIN.md](./03_RANTAI_ATRIBUSI_DAN_PENGUKURAN_KINERJA_PETUGAS_ADMIN.md) | • Rantai Atribusi Pendaftaran (`registered_by_officer_id` vs `managing_officer_id`)<br>• Engine Pengukuran Kinerja Petugas Lapangan (5 Indikator Bobot Tertimbang)<br>• Engine Pengukuran Kinerja Admin OPD (5 Pilar Tata Kelola)<br>• Keterkaitan Upah Pungut, Plafon Kas (*Cash Float Limit*), & Rekonsiliasi Kasda |
| **04** | [04_MATRIKS_PEMBAGIAN_LAYANAN_OPD_DAN_ROLE_MAPPING.md](./04_MATRIKS_PEMBAGIAN_LAYANAN_OPD_DAN_ROLE_MAPPING.md) | • Matriks 8 OPD Pemungut Aktif, OPD Pengampu Sekunder, & 11 OPD Non-Pemungut<br>• Pemetaan 24+ Jenis Retribusi Daerah & Pajak sesuai Perda 1/2024 & Perwali Baubau<br>• Matriks Otorisasi Rinci (*Permission Matrix*) antar Tingkatan Peran (*Role Tiers*)<br>• Panduan Sinkronisasi Multi-Guard Sanctum & Client Navigation Guards |

---

### Prinsip Utama Keamanan & Operasional M-PAD

1. **Role Saja Tidak Cukup (Hybrid RBAC + ABAC/ReBAC)**:
   Seorang petugas lapangan dengan role `petugas` di Dinas Lingkungan Hidup (DLH) tidak dapat melihat atau memungut retribusi pasar (Disperindag), parkir tepi jalan (Dishub), ataupun lelang ikan (Dinas Perikanan). Akses terkunci pada `opd_id`, jenis layanan (`service_code`), dan wilayah binaannya.
2. **Kemandirian Delegasi OPD (*Tenant Autonomy*)**:
   `admin_opd` (misal Admin DLH) memiliki wewenang penuh untuk membuat akun petugas dinasnya, menentukan penugasan kelurahan/blok, tanpa memerlukan intervensi `super_admin` Bapenda, dengan batasan ketat bahwa data dan petugas tidak dapat keluar dari batas instansi (`opd_id`).
3. **Segitiga Verifikasi Kasda & Kinerja**:
   Setiap rupiah penerimaan dan setiap objek baru yang terdata terikat langsung pada rantai kepemilikan:
   $$\text{Admin OPD (Pemberi Tugas)} \longrightarrow \text{Petugas (Juru Pungut/Surveyor)} \longrightarrow \text{Wajib Pajak/Retribusi (Penyetor)}$$
   Hal ini menjamin akuntabilitas audit BPK/Itda, pelaporan SIPD, serta penghitungan insentif kinerja yang objektif.
