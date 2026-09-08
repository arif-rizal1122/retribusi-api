---
title: Backlog Implementasi Modul Dinas Perhubungan (Dishub OPD ID 2) M-PAD V5
date: 2026-09-08
leantime_milestone_id: 2194
leantime_ids: [2194, 2195, 2196, 2197, 2198, 2199, 2200, 2201]
status: planned
target_milestone: M36
repos_touched: [retribusi-api, retribusi-admin, retribusi-petugas, retribusi-mobile]
---

# 📋 Rencana Aksi & Backlog Leantime: Modul Dishub M-PAD V5 (Milestone M36)

Dokumen ini mendata seluruh tiket backlog yang telah didaftarkan ke sentral **Leantime PMO** (`leantime.sarjanakomputer.id`) di bawah **Milestone 36 (ID: 2194)** untuk dieksekusi secara bertahap.

---

## 1. Daftar Tiket Leantime Milestone M36

| ID Tiket | Kode Task | Judul Tiket / Lingkup Tugas | Status Leantime | Target Platform |
| :---: | :---: | :--- | :---: | :--- |
| **#2194** | **M36** | **Milestone: Implementasi Modul Dishub M-PAD V5** (EWS Parkir, Kepelabuhanan, Smart Forms & Hermes SRE) | `status.new` (3) | Cross-Repo |
| **#2195** | **T36.1** | Skema Database & Modul Retribusi Jasa Kepelabuhanan (Tambat Labuh & Pas Dermaga) | `status.new` (3) | `retribusi-api` |
| **#2196** | **T36.2** | Tab Kepelabuhanan & Penegakan SLA $\le 24$ Jam di Admin Panel Dishub (`opd_id: 2`) | `status.new` (3) | `retribusi-admin` |
| **#2197** | **T36.3** | Nightly Anomaly Cron Scheduler (Pukul 21.00 WITA) & Tabel Riwayat EWS Log | `status.new` (3) | `retribusi-api` |
| **#2198** | **T36.4** | Otomasi Google Gemini Sparks Daily Dispatch ke Kanal Kadishub (Pukul 07.30 WITA) | `status.new` (3) | `retribusi-api` |
| **#2199** | **T36.5** | Pemetaan Spasial Poligon GIS Tempat Khusus Parkir (TKP) & Dermaga (MapPicker) | `status.new` (3) | `retribusi-admin` |
| **#2200** | **T36.6** | Hermes SRE Watchdog & Queue Buffer Resilience untuk Juru Parkir Lapangan | `status.new` (3) | Server / SRE |
| **#2201** | **T36.7** | Penghentian Karcis Manual & Penegakan Wajib QRIS Dinamis (*Zero Leakage*) | `status.new` (3) | `retribusi-petugas` |

---

## 2. Deskripsi Detail per Tiket

### #2195 (T36.1): Skema Database & Modul Retribusi Jasa Kepelabuhanan
* **Tujuan**: Membangun modul backend pencatatan tambat labuh kapal perahu motor, speed boat, dan kapal rakyat di Dermaga Jembatan Batu, Batupoopi, dan kawasan Murhum.
* **Deliverables**:
  - Migration tabel `port_docking_sessions` (nama kapal, nakhoda, gross tonnage/GT, tanggal/jam tiba, durasi).
  - Integrasi otomatis ke tabel `bills` (status: `PAID`/`PENDING`) dengan formula tarif Perda 1/2024.
  - Relasi ke `opd_id = 2`.

### #2196 (T36.2): Tab Kepelabuhanan & Penegakan SLA $\le 24$ Jam (Admin Panel)
* **Tujuan**: Memberikan antarmuka kerja khusus bagi verifikator perhubungan laut di `adminmpad.baubaukota.go.id`.
* **Deliverables**:
  - Tab baru `Kepelabuhanan` pada workspace Dishub.
  - Countdown timer SLA verifikasi teknis permohonan tambat labuh (peringatan kuning pada 18 jam, merah pada 24 jam).
  - Fitur persetujuan dan penerbitan izin labuh elektronik ber-TTE BSrE.

### #2197 (T36.3): Nightly Anomaly Cron Scheduler (21.00 WITA) & EWS Logs
* **Tujuan**: Otomasi pengawasan titik parkir dan penugasan tim Wasdal tanpa perlu menunggu admin membuka web.
* **Deliverables**:
  - Artisan command `php artisan dishub:evaluate-ews` yang dijadwalkan setiap pukul 21.00 WITA.
  - Tabel `dishub_ews_logs` untuk mencatat riwayat titik yang mengalami deviasi $> 35\%$.
  - Otomatis menerbitkan draf Surat Tugas Uji Petik digital untuk Tim Wasdal Dishub.

### #2198 (T36.4): Google Gemini Sparks Daily Dispatch (07.30 WITA)
* **Tujuan**: Pengiriman laporan eksekutif pagi langsung ke saku Kepala Dinas Perhubungan.
* **Deliverables**:
  - Scheduler cron harian pukul 07.30 WITA yang merangkum data H-1 setoran parkir dan pelabuhan.
  - Deteksi titik dormant (>24 jam 0 setoran) dan permohonan izin labuh yang pending.
  - Integrasi pesan WhatsApp/Telegram Bot resmi Kadishub.

### #2199 (T36.5): Pemetaan Spasial Poligon GIS TKP & Dermaga (MapPicker)
* **Tujuan**: Menghindari sengketa batas dan mengunci baseline potensi fiskal ruang parkir & pelabuhan.
* **Deliverables**:
  - Ekstensi MapPicker GIS untuk mendukung penggambaran poligon multi-titik (*PostGIS Polygon*).
  - Pemetaan area Pantai Kamali, Kotamara, Pasar Sentral Wameo, Terminal, dan Dermaga Jembatan Batu.
  - Penguncian batas kapasitas Satuan Ruang Parkir (SRP).

### #2200 (T36.6): Hermes SRE Watchdog & Queue Buffer Resilience
* **Tujuan**: Menjaga keandalan sistem pada jam sibuk aktivitas pasar sentral dan sandar kapal.
* **Deliverables**:
  - Buffer antrean Redis Horizon khusus endpoint sinkronisasi mobile jukir.
  - Auto-restart background worker jika memory utilization $> 90\%$.
  - Webhook alerting ke Leantime saat terjadi lonjakan latensi API $> 3$ detik.

### #2201 (T36.7): Penegakan Karcis QRIS Dinamis & Zero Leakage
* **Tujuan**: Eliminasi kebocoran retribusi tunai di lapangan.
* **Deliverables**:
  - Pembaruan kredensial default `admin.dishub@baubaukota.go.id` dan aktivasi 2FA.
  - Wajib cetak struk ber-QR token via printer thermal Bluetooth mini 58mm (ESC/POS).
  - Poin reward Sadar Pajak (PBB-P2) untuk warga yang men-scan QR struk resmi.
