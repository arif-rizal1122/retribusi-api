# Analisis Komprehensif Tata Kelola Aset Dinas Perhubungan (Dishub) Kota Baubau
**Sistem M-PAD (Manajemen Pendapatan Asli Daerah) V5**  
**OPD ID:** 2 (Dinas Perhubungan Kota Baubau)  
**Tautan Google Drive Resmi:** [Dokumen Pengelolaan Pendapatan Dishub Kota Baubau](https://docs.google.com/document/d/1EXKbVHC3CjzypBT8RaIg0uibli-uNEqd04uChVNp0f8/edit)  
**Rujukan Regulasi:** [Perda Kota Baubau No. 1 Tahun 2024](https://drive.google.com/file/d/1nVRrted30T9th42uXP0fuVH0FtO67_m5/view?usp=drivesdk) & UU HKPD No. 1/2022  
**Basis Data Audit:** Audit Data Riil PAD Kota Baubau (`Book2.xlsx`)

---

## I. Ringkasan Eksekutif: Kondisi Faktual Aset Dishub Baubau
Dinas Perhubungan Kota Baubau (OPD ID 2) merupakan salah satu pengampu aset Barang Milik Daerah (BMD) paling strategis di Kota Baubau, dengan bentang aset mencakup fasilitas maritim kepelabuhanan, simpul transportasi darat, kawasan parkir komersial, hingga sarana pengujian kendaraan.

Namun, berdasarkan data realisasi penerimaan riil eksisting (sebesar **Rp 35.641.000**), tata kelola aset Dishub saat ini menghadapi **tiga paradoks fundamental**:
1. **Konsentrasi Nilai Fiskal Ekstrem**: Penerimaan aset sangat bergantung pada dua titik saja, yaitu **Pelabuhan Jembatan Batu (54,99%)** dan **Kawasan Parkir Pantai Kamali (10,62%)**.
2. **Fenomena Titik Mati Fiskal (*Zero-Revenue Anomaly*)**: Banyak aset lahan dan bangunan komersial bernilai tinggi yang tercatat menghasilkan penerimaan **Rp 0 (Nihil)**, seperti Pelataran Kotamara, Pasar Wameo, Pelataran Umna Plaza, Islamic Center, dan Pelabuhan Lakologou.
3. **Pengelolaan Fragmentaris (*Silo Management*)**: Aset-aset di dalam satu kawasan terpadu (seperti dermaga, kios, penitipan kendaraan, dan toilet di Jembatan Batu) dikelola secara terpisah tanpa ada integrasi tata kelola kawasan pelabuhan (*Port Authority Single Framework*).

---

## II. Taksonomi & Klasifikasi Aset Dinas Perhubungan Kota Baubau
Secara fungsional dan potensi fiskal, aset Dishub Kota Baubau terbagi ke dalam 5 (lima) klaster:

| Klaster Aset | Objek Riil Lapangan | Karakteristik & Status Fiskal |
| :--- | :--- | :--- |
| **1. Kawasan Kepelabuhanan & Dermaga Laut** | • Pelabuhan Jembatan Batu<br>• Pelabuhan Sulaa<br>• Pelabuhan Tarafu<br>• Pelabuhan Lakologou | • Retribusi Jasa Usaha (Pasal 84 ayat 1 f & Pasal 91)<br>• Tambat labuh (GT × hari), pas masuk, dan muat BBM/tambang. |
| **2. Bangunan & Ruang Komersial Pelabuhan** | • 15 Kios/Petak Jembatan Batu<br>• Area Penitipan Truk & Bus Jbt Batu<br>• MCK Pelabuhan Jembatan Batu<br>• Lahan Bongkar Muat Barang | • Retribusi Pemanfaatan Aset BMD (Pasal 84 ayat 1 i)<br>• Tarif sewa kios Rp 120.000/bln<br>• Tarif penitipan Rp 25.000/unit |
| **3. Tempat Khusus Parkir (TKP) Luar Badan Jalan** | • Kawasan Pantai Kamali<br>• Eks Kantor Pol PP Lama<br>• Pelataran Kotamara<br>• Pelataran Pasar Wameo & Plaza | • Retribusi Jasa Usaha (Pasal 84 ayat 1 c & Pasal 88)<br>• Potensi okupasi pihak ketiga dan jukir tidak resmi. |
| **4. Fasilitas Pengujian Kendaraan (Uji KIR)** | • Gedung Uji Kendaraan Bermotor<br>• Line Uji Mekanis (Brake tester, Smoke tester, Headlight tester) | • Non-Retribusi (Cost Center)<br>• Retribusi dihapus per UU HKPD 1/2022 & Perda 1/2024. |
| **5. Perlengkapan Jalan & Fasilitas Penunjang** | • Traffic Light (APILL), Rambu Jalan<br>• Halte Angkutan Kota<br>• Pos Jaga & Portal Retribusi | • Aset Pelayanan Publik Murni<br>• Membutuhkan alokasi belanja pemeliharaan rutin. |

---

## III. Diagnosa Kritis: Masalah Pokok Tata Kelola Aset Eksisting
1. **Belum Adanya Master Data Aset Terintegrasi Geospasial (*GIS Asset Registry*)**: Aset-aset belum dipetakan secara digital dalam layer spasial PostGIS yang memuat polygon boundary, luas, kapasitas, dan legalitas hak pakai. Terjadi tumpang tindih yurisdiksi dengan instansi vertikal/pedagang.
2. **Sewa Kios dan Lahan Pelabuhan Masih Manual & Rentan Tunggakan**: 15 petak kios Jembatan Batu ditarik karcis fisik tanpa kontrak digital dan tanpa jatuh tempo otomatis. Uang sewa rentan mengendap lama di petugas pos.
3. **Kebuntuan Penataan Aset TKP Akibat Okupasi Liar & Konflik Sosial**: Pelataran Kotamara dan Pasar Wameo dikuasai pihak ketiga tidak resmi. Penertiban represif berisiko sosial, sehingga menghasilkan setoran Rp 0.
4. **Disparitas Kawasan Pelabuhan Lakologou (Aset Mati Bernilai Tinggi)**: Dermaga komoditas tambang/aspal bertarif Perda Rp 20.000/ton mencatat realisasi Rp 0 karena ketiadaan sistem verifikasi manifes tambat tonase.
5. **Perubahan Status Gedung Uji KIR Menjadi Idle Cost Center**: Retribusi KIR resmi dihapus per UU HKPD 1/2022, namun gedung dan alat uji tetap menuntut beban pemeliharaan operasional APBD.

---

## IV. Arsitektur Solusi Transformasi Tata Kelola Aset di M-PAD
```
                 [CORE ENGINE M-PAD DISHUB]
                             │
     ┌───────────────────────┼───────────────────────┐
     ▼                       ▼                       ▼
[ZONASI PORT MASTER]   [E-BILLING RECURRING]   [KEMITRAAN TKP & JUKIR]
(Jembatan Batu & Sulaa) (15 Kios & Lahan Inap)  (Pantai Kamali & Kotamara)
     │                       │                       │
• Dermaga Tambat Labuh  • Tagihan Bulanan Auto  • Registrasi Wilayah Parkir
• Gate Pas Masuk        • QRIS / VA Bank Sultra • Bagi Hasil Resmi 70:30
• Penitipan Truk Rp25rb • Pengingat WA Otomatis • Jukir Wallet & BPJS TK
• Rekonsiliasi Kasda T+0 • Riwayat Bayar Bersih • Satgas Gabungan Forkopimda
```

1. **Implementasi Port Master Object (Zonasi Kawasan Terpadu)**: Jembatan Batu didaftarkan sebagai 1 Parent Object dengan 4 Sub-Objects (Dermaga, Gate Pas Masuk, Penitipan Kendaraan Rp 25.000, 15 Kios Rp 120.000, MCK).
2. **Digitalisasi Kontrak & Tagihan Sewa Kios Otomatis (*e-Billing Recurring*)**: 15 tenant terdata NIK. e-SKRD terbit otomatis tanggal 1 tiap bulan (Rp 120.000) terhubung QRIS & VA Bank Sultra (100% Direct Settling).
3. **Skema Kemitraan Pengelolaan Lahan TKP (Legalitas & Kesejahteraan)**: Lahan parkir liar diformalkan lewat SK Tugas resmi, didukung aplikasi kasir cepat (Quick-Tap ESC/POS 58mm), bagi hasil sah 70:30, dan jaminan BPJS Ketenagakerjaan.
4. **Pengaktifan Pengawasan Manifes Dermaga Lakologou**: Integrasi SSRD untuk komoditas industri tambang/konstruksi bertarif Rp 20.000/ton host-to-host Bank Sultra.
5. **Optimalisasi Aset Balai Pengujian Kendaraan (Ex-Uji KIR)**: Menjadi pusat keselamatan transportasi dan penjajakan sewa kalibrasi/Kerja Sama Pemanfaatan (KSP) armada BUMN/swasta.

---

## V. Matriks Tindak Lanjut Tata Kelola Aset Dishub

| Tahap Pelaksanaan | Fokus Tindakan Tata Kelola Aset | Target Capaian Output |
| :--- | :--- | :--- |
| **Tahap I: Konsolidasi & Legalitas** *(Bulan 1–2)* | • Inventarisasi fisik & pemetaan geospasial (GIS) seluruh aset pelabuhan, TKP, dan bangunan kios.<br>• Registrasi ulang 15 penyewa kios Jembatan Batu ke basis data M-PAD.<br>• Penerbitan SK Wali Kota mengenai Satgas Penataan Aset & Perparkiran bersama Forkopimda. | Terbentuknya Digital Asset Registry Dishub dan basis data penyewa terverifikasi NIK. |
| **Tahap II: Digitalisasi Operasional** *(Bulan 3–4)* | • Penerapan e-SKRD Recurring Billing untuk 15 kios dan penitipan kendaraan Jembatan Batu.<br>• Uji coba integrasi kawasan Jembatan Batu dan digitalisasi parkir Pantai Kamali.<br>• Sosialisasi plang aset resmi Pemkot Baubau oleh Diskominfo & Bapenda. | 100% penerimaan sewa kios pelabuhan masuk langsung via kanal non-tunai Bank Sultra. |
| **Tahap III: Penataan Titik Mati** *(Bulan 5–6)* | • Mediasi meja bundar penataan aset TKP Kotamara dan Pasar Wameo.<br>• Aktivasi modul manifes tambatan curah di Pelabuhan Lakologou.<br>• Evaluasi berkala deviasi potensi vs realisasi aset via Early Warning System (EWS). | Menghilangkan status Rp 0 pada aset Kotamara dan Pelabuhan Lakologou. |
| **Tahap IV: Modernisasi Penuh** *(Bulan 7–8+)* | • Pemasangan Smart Barrier Gate terintegrasi pada pintu masuk Pelabuhan Jembatan Batu dan Pantai Kamali.<br>• Kerja sama pemanfaatan aset (KSP) komersial untuk area pelabuhan yang belum tergarap. | Optimalisasi nilai aset (*asset yield*) Dishub meningkat 300%–500% secara akuntabel. |

---

## VI. Penugasan Aktor & Role Khusus: Admin/Pengelola Aset Dishub (`admin_aset_dishub`)
Untuk menjamin pemisahan fungsi (*Separation of Duties*) antara operator kasir jukir harian dengan pengelola kontrak kekayaan daerah, disiapkan role khusus:
- **Role Code:** `admin_aset_dishub` / `dishub_aset`
- **Tupoksi Utama:**
  1. Mengelola registrasi penyewa kios, nomor kontrak, dan masa aktif sewa 15 Kios Jembatan Batu.
  2. Melakukan approval dan verifikasi permohonan sewa lahan/kios di modul **Sewa Aset M-PAD** (`/asset/rentals`).
  3. Memantau buku besar tagihan e-SKRD recurring bulanan (Rp 120.000 / tenant).
  4. Memonitor utilisasi lahan penitipan kendaraan truk inap dan dermaga bongkar muat.
- **Akun Standar Sistem:**
  - **Email:** `aset.dishub@baubaukota.go.id`
  - **OPD ID:** 2 (Dinas Perhubungan Kota Baubau)
