# 📄 Laporan Integritas Dokumen Resmi BAPENDA (4 Tahapan)

**Waktu Eksekusi**: 2026-03-17 06:54:06
Pengujian ini memverifikasi bahwa seluruh dokumen resmi dapat dicetak/tampil sebagai PDF tanpa error (40x/50x).

## 🧪 Hasil Pengujian Lintas Tahapan

| Tahapan | Nama Dokumen | Endpoint | Status | Hasil | Error (Jika Ada) |
| :--- | :--- | :--- | :---: | :---: | :--- |
| 1. Pendaftaran | SKT (Surat Keterangan Terdaftar) | `/api/documents/skt/1` | ✅ 200 | ✅ PDF | - |
| 2. Pendataan | LKOK (Lembar Kerja Objek Khusus) | `/api/documents/lkok/1` | ✅ 200 | ✅ PDF | - |
| 3. Penetapan | SKRD (Retribusi) | `/api/documents/skrd/1` | ✅ 200 | ✅ PDF | - |
| 3. Penetapan | SPPT (PBB) | `/api/documents/sppt/1` | ✅ 200 | ✅ PDF | - |
| 3. Penetapan | SKPDKBT (Kurang Bayar) | `/api/documents/skpdkbt/1` | ✅ 200 | ✅ PDF | - |
| 3. Penetapan | SKPDN (Nihil) | `/api/documents/skpdn/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | SSPD (Bukti Bayar) | `/api/documents/sspd/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | SSRD (Retribusi) | `/api/documents/ssrd/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | STRD (Tagihan Retribusi) | `/api/documents/strd/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | SPP (Tugas Pemeriksaan) | `/api/documents/spp/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | SPMP (Surat Paksa) | `/api/documents/spmp/1` | ✅ 200 | ✅ PDF | - |
| 4. Penagihan | Notice Detail PDF | `/api/pengawas/enforcements/1/pdf` | ✅ 200 | ✅ PDF | - |
| Public | Public SKRD | `/api/public/pdf/skrd/1` | ✅ 200 | ✅ PDF | - |
| Public | Public SSPD | `/api/public/pdf/sspd/1` | ✅ 200 | ✅ PDF | - |

---
### 📈 Ringkasan Eksekusi
- Total Pengujian: 14
- Sukses: 14
- Gagal: 0

> [!TIP]
> Seluruh dokumen dalam 4 tahapan berfungsi dengan baik dan siap cetak.
