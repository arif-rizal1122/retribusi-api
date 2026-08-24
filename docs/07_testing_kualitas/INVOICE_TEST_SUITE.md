# M-PAD Invoice Test Suite

Dokumen ini berisi daftar skenario pengujian untuk memvalidasi integritas sistem penagihan (invoicing) di seluruh platform.

---

## 🏗️ Skenario 1: Official Assessment (Penetapan Jabatan)
*Target: Usaha tetap dengan tarif flat/variabel tetap (Contoh: Retribusi Sampah, PBB-P2).*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 1.1 | Pendaftaran Objek Pajak baru dengan metadata lengkap. | Masyarakat/Petugas | Data tersimpan di database dengan status `pending`. |
| 1.2 | Persetujuan (Approval) pendaftaran di dashboard. | Admin | Status objek menjadi `active` dan **Bill (SKRD)** pertama terbit otomatis. |
| 1.3 | Periksa detail Bill di dashboard Admin. | Admin | Nomor invoice sesuai format (`INV-...`), nominal sesuai tarif pusat/zona. |
| 1.4 | Bayar tagihan via Tunai di loket. | Petugas/Admin | Status Bill berubah menjadi `lunas`, record `Payment` tercipta. |

---

## 📝 Skenario 2: Self-Assessment (Pelaporan Mandiri)
*Target: Usaha berbasis omzet (Contoh: Pajak Hotel, Restoran, Parkir).*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 2.1 | Input Laporan Omzet Bulanan (SPTPD). | Masyarakat | Record `MonthlyReport` tercipta dengan status `pending`. |
| 2.2 | Verifikasi dan Persetujuan laporan. | Admin | Status laporan `approved`, **Bill (SKRD)** terbit otomatis berdasarkan omzet. |
| 2.3 | Simulasi penolakan (Reject) laporan. | Admin | Status laporan `rejected`, tidak ada Bill yang terbit. Pesan alasan muncul di Mobile. |
| 2.4 | WP membayar via QRIS/VA dan klaim bukti bayar. | Masyarakat | Status Payment `pending` (menunggu verifikasi bukti manual). |

---

## 🤳 Skenario 3: Penagihan Lapangan (QR Scan & JIT)
*Target: Operasional harian petugas saat surveillance.*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 3.1 | Scan QR Objek Pajak yang memiliki tunggakan lama. | Petugas | Muncul daftar **Virtual Bill** (Jit Billing) untuk semua bulan tertinggal. |
| 3.2 | Pilih 2 dari 5 bulan tunggakan untuk dibayar. | Petugas | Tercipta 2 record Payment sukses, sisa 3 bulan tetap tertunggak. |
| 3.3 | Verifikasi keabsahan denda pada Virtual Bill. | Petugas | Denda muncul otomatis (1-2%/bulan) berdasarkan `due_date`. |

---

## ⌚ Skenario 4: Denda & Jatuh Tempo (Penalty Automation)
*Target: Validasi integritas finansial dan regulasi.*

| No | Langkah Pengujian | Peran | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 4.1 | Ubah `due_date` bill yang ada menjadi H-60 (manual DB). | Developer | - |
| 4.2 | Jalankan command `php artisan bills:calculate-penalties`. | Admin (CLI) | Kolom `penalty_amount` pada bill tersebut terisi otomatis (bunga 2 bulan). |
| 4.3 | Bayar bill yang sudah terkena denda. | WP/Petugas | Total bayar = Pokok + Denda. Status bill menjadi `lunas`. |

---

## ⚠️ Skenario Negatif (Edge Cases)
- **Double Payment**: Mencoba membayar periode yang sama di dua device berbeda secara bersamaan.
- **Formulasi Error**: Pendaftaran objek tanpa zona (jika zona wajib) -> Pastikan sistem tidak *crash* saat menghitung JIT.
- **Metadata Missing**: Menghapus metadata Luas M2 pada objek Reklame -> Periksa apakah billing jatuh ke nilai *fallback*.

---
> *Skema ini dirancang untuk memastikan nol kebocoran pendapatan daerah melalui validasi sistem yang ketat.*
