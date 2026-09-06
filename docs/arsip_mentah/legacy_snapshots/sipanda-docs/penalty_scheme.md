# Skema Penalty & Denda (MITRA)

Dokumen ini menjelaskan mekanisme perhitungan sanksi administrasi (bunga), denda keterlambatan lapor, serta prosedur penghapusan denda (amnesty) berdasarkan **Perwali No. 58/2024**.

## 1. Sanksi Administrasi (Bunga Keterlambatan)

Sanksi bunga dihitung dari total pokok pajak yang belum dibayar. Perhitungannya dimulai 1 hari setelah melewati **Jatuh Tempo (Due Date)**.

### Tarif Bunga per Bulan
Tarif bunga dibedakan berdasarkan jenis penetapan atau kondisi keterlambatan:

| Jenis Sanksi | Tarif / Bulan | Keterangan |
| :--- | :--- | :--- |
| **STPD** | 1.0% | Keterlambatan Pembayaran/Setoran (Standar) |
| **SKPDKB** | 1.8% | Hasil Pemeriksaan Umum |
| **Jabatan** | 2.2% | Pemeriksaan karena tidak lapor/tidak pembukuan |
| **Lainnya** | 0.6% | Angsuran, Penundaan, atau Salah Hitung |

### Aturan Perhitungan Waktu
- **Bagian Bulan dihitung Penuh**: Keterlambatan 1 hari tetap dihitung sebagai 1 bulan penuh.
- **Maksimal Durasi**: Sanksi bunga maksimal dikenakan untuk **24 bulan**.
- **Rumus**: `Sanksi = Pokok x Tarif x Jumlah Bulan (Max 24)`

---

## 2. Denda Keterlambatan Lapor (Fixed Fine)

Khusus untuk jenis pajak **Self Assessment** (Wilayah II), wajib pajak wajib melaporkan omzetnya (SPTPD) setiap bulan.
- **Denda**: Rp 100.000,- (flat) jika tidak melapor tepat waktu.

---

## 3. Ketentuan Khusus PBB-P2

PBB-P2 memiliki kebijakan khusus terkait masa tenggang:
- **Jatuh Tempo Standar**: Biasanya 10 November pada tahun berjalan.
- **Grace Period Baru**: Wajib Pajak baru (Pendaftaran Baru) diberikan masa tenggang **6 bulan** sejak pendaftaran sebelum mulai dikenakan bunga jika belum bayar.

---

## 4. Amnesty & Waiver (Penghapusan Denda)

Sistem MITRA mendukung pengajuan penghapusan atau pengurangan denda melalui modul **Amnesty**.

### Prosedur
1. **Pengajuan**: Petugas lapangan atau Admin mengajukan permohonan atas permintaan Wajib Pajak dengan menyertakan alasan.
2. **Jenis Pengurangan**:
   - **Persentase**: Contoh: Pengurangan 50% atau 100% (penghapusan total).
   - **Nominal Tetap**: Contoh: Pengurangan sebesar Rp 50.000.
3. **Persetujuan**: Harus disetujui oleh **Kabid** atau **Super Admin** melalui dashboard sebelum tagihan (Bill) diperbarui.

---

## 5. Otomasi Sistem
Sistem menjalankan command `bills:calculate-penalties` secara berkala (Cron Job) untuk memperbarui nilai sanksi pada seluruh tagihan yang berstatus `pending` dan telah melewati jatuh tempo.
