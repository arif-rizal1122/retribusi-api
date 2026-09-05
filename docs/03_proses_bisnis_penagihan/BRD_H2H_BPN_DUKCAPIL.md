# BUSINESS REQUIREMENTS DOCUMENT (BRD)
## Integrasi Host-to-Host (H2H) M-PAD dengan ATR/BPN (BHUMI & KKP) dan Ditjen Dukcapil

---

**Nomor Dokumen:** BRD-MPAD-H2H-001  
**Versi:** 1.0.0  
**Tanggal:** 5 September 2026  
**Penyusun:** Tim Arsitektur & Analis Sistem M-PAD (Bapenda Kota Baubau)  
**Status:** Disetujui untuk Implementasi  
**Target Milestone Leantime:** [M35: Integrasi H2H ATR/BPN (BHUMI, KKP) & Dukcapil](https://leantime.sarjanakomputer.id) (Milestone ID: 2180)

---

## 1. Latar Belakang & Urgensi Bisnis

Pemerintah Kota Baubau melalui Badan Pendapatan Daerah (Bapenda) mengoperasikan platform terpadu **M-PAD** untuk mengelola Pajak Bumi dan Bangunan Perdesaan dan Perkotaan (PBB-P2) serta Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB). Dalam kerangka implementasi kebijakan nasional Satu Data Indonesia, optimalisasi penerimaan PAD, dan pemenuhan instrumen **Monitoring Center for Prevention (MCP) KPK**, dibutuhkan integrasi data secara langsung (Host-to-Host) antara Bapenda Kota Baubau dengan:
1. **Kementerian ATR/BPN (Kantor Pertanahan Kota Baubau):** Melalui Portal BHUMI dan Komputerisasi Kantor Pertanahan (KKP) untuk sinkronisasi NIB, batas geometri bidang tanah (Polygon Spasial), dan Zona Nilai Tanah (ZNT).
2. **Kementerian Dalam Negeri (Ditjen Dukcapil):** Untuk validasi keabsahan identitas Nomor Induk Kependudukan (NIK) Wajib Pajak secara *real-time*.

---

## 2. Sasaran & Tujuan Bisnis (Business Objectives)

1. **Eliminasi Human Error & Beban Warga (Citizen-Centric Automation):**
   - Warga tidak lagi diwajibkan menghafal atau menginput NIB (Nomor Identifikasi Bidang) secara manual.
   - NIB ditarik secara otomatis (*auto-pull*) melalui koordinat GIS / poligon peta atau pencarian NOP/Nomor Sertipikat via H2H BPN BHUMI.
2. **Pencegahan *Under-Invoicing* & Kebocoran PAD pada Transaksi BPHTB:**
   - Menegakkan nilai wajar Nilai Perolehan Objek Pajak (NPOP Floor). Jika NPOP yang dilaporkan PPAT/Pemohon berada di bawah nilai ZNT resmi ATR/BPN, sistem secara otomatis menetapkan Nilai ZNT sebagai dasar pengenaan pajak atau membubuhkan status *Soft Flag* untuk audit verifikator.
3. **Penerbitan Layanan Pertanahan Seketika (Real-Time Service Clearance):**
   - Transaksi pelunasan PBB-P2 dan BPHTB di M-PAD langsung mengirimkan notifikasi lunas ke sistem KKP BPN melalui integrasi SPLP (*Sistem Penghubung Layanan Pemerintah*), sehingga proses validasi sertipikat dan peralihan hak di BPN dapat berlangsung tanpa surat keterangan lunas manual fisik.
4. **Validasi Subjek Pajak Otentik (KYC Terintegrasi Dukcapil):**
   - Mencegah data ganda, NIK fiktif, dan sengketa kepemilikan objek pajak dengan verifikasi NIK langsung ke database kependudukan nasional.
5. **Pemenuhan Indikator MCP KPK Bidang Optimalisasi Pajak Daerah:**
   - Memastikan Pemkot Baubau meraih nilai maksimal pada indikator tematik integrasi BPHTB-BPN, PBB-BPN, dan pemanfaatan data NIK.

---

## 3. Cakupan Kebutuhan Fungsional Bisnis

### 3.1. Alur Pendaftaran Objek PBB Baru (PBB-P2 Registration)
- **FR-BRD-01 (Auto-Pull NIB):** Saat pemohon menggambar atau memilih poligon bidang tanah pada peta di aplikasi mobile warga (`retribusi-mobile`) atau portal web, backend M-PAD mengirimkan permintaan *spatial reverse query* ke API BHUMI BPN.
- **FR-BRD-02 (Data Auto-Populate):** Data NIB, Kode Zona ZNT, Nilai Indikasi Rata-rata (NIR), dan estimasi luas tanah legal otomatis terisi di formulir dengan tanda *badge* hijau "Terverifikasi ATR/BPN".
- **FR-BRD-03 (Graceful Fallback Non-Sertipikat):** Apabila bidang tanah pemohon belum bersertipikat / belum terpetakan di ATR/BPN (misalnya tanah adat/sporadik/girik), sistem tidak memblokir permohonan. Formulir dialihkan ke mode "Pendaftaran Mandiri / Non-Sertipikat" dengan status NIB `NULL` dan diteruskan ke verifikasi faktual petugas lapangan (`retribusi-petugas`).

### 3.2. Alur Transaksi dan Penetapan e-BPHTB
- **FR-BRD-04 (Interlock NIB - NOP BPHTB):** Notaris/PPAT memasukkan NOP dan/atau NIB saat pelaporan transaksi BPHTB. Sistem mencocokkan data pada tabel `nib_nop_mappings` dan memanggil API KKP BPN.
- **FR-BRD-05 (Validasi Nilai Wajar NPOP Floor):**
  $$\text{NPOP Minimal} = \text{Luas Tanah Legal} \times \text{NIR ZNT BPN}$$
  Jika $\text{NPOP Riil Transaksi} < \text{NPOP Minimal}$, sistem menerapkan aturan bisnis:
  - Default: Menghitung BPHTB terutang menggunakan Nilai ZNT BPN (`final_npop = znt_value * area`).
  - Flag Audit: Memberikan label `UNDER_ZNT_FLAG` pada berkas BPHTB untuk diverifikasi oleh Pejabat Fungsional Bapenda.
- **FR-BRD-06 (Discretionary Override):** Petugas Verifikator Bapenda dengan wewenang khusus dapat menyetujui nilai transaksi riil jika disertai dokumen pendukung yang sah (misalnya putusan lelang pengadilan atau hibah waris sedarah).

### 3.3. Alur Notifikasi Pelunasan Pajak (Outbound SPLP BPN)
- **FR-BRD-07 (Webhook Pelunasan Otomatis):** Setiap kali pembayaran tagihan PBB atau invoice BPHTB sukses dibukukan (melalui Bank Sultra, BRI, QRIS, atau kanal perbankan lain), M-PAD wajib mengirimkan *payload* konfirmasi lunas ke endpoint BPN SPLP.
- **FR-BRD-08 (Non-Blocking Payment Resilience):** Kegagalan jaringan atau timeout dari server BPN saat pengiriman webhook pelunasan tidak boleh membatalkan (*rollback*) transaksi pembayaran kas daerah di bank. Transaksi kas tetap sah, dan pengiriman notifikasi ke BPN dikelola melalui mekanisme antrean asinkron (*retry queue*).

### 3.4. Alur Validasi Identitas Kependudukan (Dukcapil)
- **FR-BRD-09 (Validasi NIK Pemohon):** Saat warga mendaftar akun atau mengajukan mutasi objek pajak, NIK divalidasi ke layanan H2H Ditjen Dukcapil untuk memastikan nama, tempat tanggal lahir, dan status kependudukan sesuai dengan KTP elektronik.

---

## 4. Analisis Pemangku Kepentingan (Stakeholders)

| Pihak | Peran dalam Ekosistem | Manfaat Utama |
| :--- | :--- | :--- |
| **Wajib Pajak (Warga)** | Pemilik objek pajak & pemohon layanan | Tidak perlu repot mencari NIB, proses penetapan PBB lebih cepat dan akurat. |
| **Bapenda Kota Baubau** | Otoritas pemungut PBB dan BPHTB | Mencegah kebocoran PAD, data objek sinkron dengan peta kadaster nasional. |
| **Kantor Pertanahan ATR/BPN** | Pengelola data kadaster & pendaftaran tanah | Kepastian pelunasan PBB & BPHTB sebelum menerbitkan sertipikat, integrasi satu peta. |
| **Notaris / PPAT** | Pelapor akta transaksi peralihan hak | Validasi instan kebenaran NOP, NIB, dan batas kewajaran nilai pajak secara digital. |
| **Bank Persepsi (Bank Sultra / BRI)** | Kanal penerima pembayaran PAD | Penerimaan kas daerah lancar tanpa dependensi ke server eksternal BPN saat *settlement*. |
| **Inspektorat & KPK RI** | Pengawas tata kelola dan pencegahan korupsi | Kepatuhan terhadap 8 Area Intervensi MCP KPK (optimalisasi pajak daerah). |

---

## 5. Batasan & Dependensi Bisnis
1. **Perjanjian Kerja Sama (PKS / MoU):** Akses API produksi BHUMI/KKP ATR/BPN dan Dukcapil memerlukan persetujuan teknis dan pertukaran *production secret key*.
2. **Ketersediaan Data Spasial Baubau di BPN:** Sebagian bidang tanah di Kota Baubau belum dilakukan pemetaan lengkap (PTSL belum mencakup 100% wilayah), sehingga sistem wajib mendukung fleksibilitas fallback non-sertipikat.
