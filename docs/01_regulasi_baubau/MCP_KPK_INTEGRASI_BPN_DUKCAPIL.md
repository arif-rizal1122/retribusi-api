# MASTER CONTROL PLAN (MCP) & KEPATUHAN MCP KPK
## Integrasi Interlock Perpajakan Daerah (PBB-P2 & BPHTB) dengan ATR/BPN & Dukcapil Kota Baubau

---

**Nomor Dokumen:** MCP-MPAD-KPK-001  
**Area Intervensi:** Optimalisasi Pajak Daerah (Tematik 8 Area MCP KPK)  
**Sub-Indikator:** Integrasi Sistem Bapenda dengan Kantor Pertanahan (H2H BPHTB & PBB) dan Pemanfaatan NIK Dukcapil  
**Tahun Penilaian:** 2026 / 2027  
**Penanggung Jawab:** Bapenda Kota Baubau & Tim Pengembang Ekosistem M-PAD  
**Milestone Leantime:** [M35: Integrasi H2H ATR/BPN & Dukcapil](https://leantime.sarjanakomputer.id) (ID: 2180)

---

## 1. Pemetaan Indikator & Parameter Penilaian MCP KPK

Berdasarkan pedoman Penilaian Indikator Tata Kelola Pemerintah Daerah (MCP) Komisi Pemberantasan Korupsi (KPK) Republik Indonesia untuk sektor **Optimalisasi Pajak Daerah**:

| Sub-Indikator MCP KPK | Target Capaian (100%) | Implementasi Teknis pada M-PAD | Status Sistem |
| :--- | :--- | :--- | :---: |
| **1. Integrasi H2H BPHTB - Kantor Pertanahan** | Pertukaran data validasi pembayaran BPHTB dan data pertanahan (NIB/ZNT) secara *real-time* tanpa intervensi tatap muka. | Pembuatan controller `H2HBphtbController`, `NibNopMapping`, pengesahan e-SSPD ber-QR Code TTE (`issueSspd`), notifikasi pelunasan SPLP otomatis asinkron, dan validasi nilai wajar NPOP vs ZNT BPN. | **Siap Uji H2H** |
| **2. Integrasi Data PBB-P2 dengan Peta Bidang Tanah (BPN)** | Pemadanan NOP PBB dengan NIB BPN dan sinkronisasi batas spasial (Geometri Poligon). | Penyediaan field `nib_code`, `polygon_geom`, `znt_zone_code`, dan endpoint `reverse-lookup` BHUMI BPN di `retribusi-api`. | **Fase Migrasi Skema** |
| **3. Pemanfaatan Data NIK (Ditjen Dukcapil)** | Pemadanan data subjek pajak dengan basis data kependudukan nasional untuk validasi Wajib Pajak. | Layanan `DukcapilNikService` terhubung ke API Dukcapil untuk validasi KTP elektronik secara *real-time* saat pendaftaran akun warga. | **Tersedia (Stub Aktif)** |
| **4. Pengawasan Transaksi Tidak Wajar (*Under-Invoicing*)** | Sistem pencegahan transaksi BPHTB di bawah nilai pasar wajar melalui komparasi ZNT. | Penegakan aturan `UNDER_ZNT_FLAG` pada simulasi BPHTB; transaksi di bawah ZNT otomatis terkunci untuk review supervisor. | **Telah Berjalan** |

---

## 2. Matriks Pengendalian Risiko & Analisis Gangguan Sistem (Systemic Impact Analysis)

Sebelum mengaktifkan skema H2H ini ke lingkungan produksi, dilakukan audit menyeluruh terhadap seluruh komponen ekosistem M-PAD untuk mengidentifikasi potensi gangguan (*system disruption*) dan langkah mitigasinya:

| Area / Modul Terdampak | Potensi Masalah / Gangguan yang Terjadi | Tingkat Dampak | Solusi & Strategi Mitigasi (Fail-Safe) |
| :--- | :--- | :---: | :--- |
| **1. Modul Pendaftaran Objek PBB (`pbb_nop_applications`)** | Jika sistem mewajibkan NIB BPN secara mutlak, warga yang memiliki tanah belum bersertipikat (tanah adat / girik / sporadik) akan terblokir dan tidak bisa mendaftarkan PBB. | **Tinggi (Kritis)** | **Dual-Mode Registration (Graceful Fallback):** NIB dibuat opsional (`nullable`). Jika poligon tidak menghasilkan kecocokan di BHUMI, sistem beralih ke mode pendaftaran mandiri dengan verifikasi lapangan oleh petugas (`retribusi-petugas`). |
| **2. Modul e-BPHTB Notaris / PPAT (`bphtb_submissions`)** | Penolakan kaku transaksi jika NPOP < ZNT BPN dapat memicu perselisihan hukum dan memblokir transaksi sah yang memiliki alasan khusus (misal lelang negara, warisan keluarga). | **Sedang** | **Soft-Flagging & Discretionary Approval:** Sistem tidak melakukan *hard-reject (422)*, melainkan menetapkan `status_flag = 'UNDER_ZNT_FLAG'`. Pejabat Bapenda diberikan wewenang *Supervisor Override* dengan kewajiban melampirkan dasar berita acara. |
| **3. Payment Gateway & Bank Settlement (Bank Sultra / BRI)** | Jika notifikasi lunas ke webhook BPN (SPLP) dimasukkan dalam *synchronous execution* callback bank, keterlambatan respons dari server BPN akan menyebabkan timeout pada callback perbankan, memicu kegagalan pembukuan kas daerah. | **Kritis (Fatal)** | **Asynchronous Decoupled Queue Worker:** Callback bank langsung dijawab `200 Success` dalam < 1.5 detik. Notifikasi pelunasan ke BPN dijalankan oleh Laravel Background Queue Worker dengan 5x auto-retry. Kas daerah 100% aman. |
| **4. Kinerja Kueri Database (Spatial N+1 Queries)** | Kueri poligon spasial PostGIS skala ribuan bidang tanah dapat membebani CPU database jika tidak memiliki indeks spasial GiST. | **Sedang** | Penambahan indeks spasial eksplisit: `CREATE INDEX idx_pbb_objects_polygon_gist ON taxpayer_pbb_objects USING GIST(polygon_geom);`. |
| **5. Privasi Data Kependudukan (Dukcapil)** | Akses data kependudukan sensitif melanggar UU Pelindungan Data Pribadi (UU PDP No. 27/2022) jika log API menyimpan NIK dan data keluarga secara terang-terangan. | **Tinggi** | Seluruh *request payload* dan *response* Dukcapil disaring (*data masking*) pada tabel log audit `h2h_connector_logs`. |

---

## 3. Protokol Pembaruan Berkelanjutan di Leantime (Project Governance)

Untuk menjaga akuntabilitas pelaksanaan:
1. Setiap tiket di Leantime Milestone **M35** (ID: 2180) wajib diperbarui statusnya secara *real-time*:
   - Saat perencanaan/riset: `status = 3` (To Do / New)
   - Saat koding dan pengetesan lokal: `status = 4` (In Progress)
   - Saat menunggu pengujian bersama pihak BPN/Dukcapil: `status = 1` (Blocked / Waiting External)
   - Setelah lulus verifikasi dan deploy production: `status = 0` (Done)
2. Log komit pada git harus mencantumkan referensi tiket Leantime (contoh: `git commit -m "feat(h2h): T35.2 implement spatial reverse lookup for NIB auto-pull"`).
