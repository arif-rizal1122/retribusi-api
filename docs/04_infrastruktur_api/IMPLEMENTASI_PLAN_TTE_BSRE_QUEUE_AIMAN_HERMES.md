# 🚀 IMPLEMENTATION PLAN TTE BSRE & PROTOKOL ORKESTRASI QUEUE
## Koordinasi Antara Antigravity IDE ↔ Aiman VPS (API) ↔ Hermes SRE Watchdog

Dokumen ini memetakan rencana kerja implementasi teknis, arsitektur message queue, mitigasi kegagalan, dan agenda konsultasi resmi dengan tim **Balai Sertifikasi Elektronik (BSrE - BSSN)** pada 17 September 2026.

---

## 1. Rincian Agenda & Rapat Zoom Konsultasi Pra-Integrasi BSrE

- **Penyelenggara**: Pemerintah Kota Baubau & BSSN (BSrE Pusat)
- **Topik**: Konsultasi Pra-integrasi modul E-Sign BSrE dengan aplikasi Mitra M-PAD Kota Baubau
- **Waktu**: Kamis, 17 September 2026, Pukul 09:00 AM Singapore / WITA (08:00 WIB)
- **Zoom Link**: [Join Zoom Meeting](https://us06web.zoom.us/j/7946502742?pwd=ZPR86w6gBNIbb0SYYtE7Lt9EYJwIV4.1&omn=81058679048)
- **Meeting ID**: `794 650 2742` | **Passcode**: `baubau01`
- **SIP**: `7946502742@zoomcrc.com`

---

## 2. Matriks Penugasan Terstruktur (Task Pool Backlog)

Format penomoran tiket diselaraskan dengan papan koordinasi Leantime PMO (`leantime.sarjanakomputer.id`):

| Task ID | Nama Penugasan & Lingkup Kerja | Pemilik | Lingkungan Eksekusi | Target Output / Deliverable | Status |
|:---|:---|:---|:---|:---|:---|
| **[SPARK-026]** | Spesifikasi Layout PDF/A-1b Bukti Lunas Lintas 8 OPD | Spark | Planning & Architecture | Format Blade PDF/A-1b & Visual Stamp QR Code 2.5 x 2.5 cm | **DONE** |
| **[SPARK-027]** | SOP Fallback "Kode Numerik Resmi OPD" untuk Aset BMD | Spark | Kebijakan & Regulasi | Naskah klausul transisi & skema token verifikasi mandiri | **DONE** |
| **[SPARK-028]** | Lembar Pertanyaan Kunci & Bahan Diskusi Zoom BSrE | Spark | Analisis Teknis | Daftar 5 poin krusial untuk sesi tanya-jawab rapat | **DONE** |
| **[AIMAN-039]** | Implementasi REST API Client BSrE pada `TTEService.php` | Aiman | Antigravity IDE (PHP 8.4) | Service wrapper H2H BSrE (`/api/sign/pdf`), mTLS, SHA-256 | **READY** |
| **[AIMAN-040]** | Pembangunan Auto-TTE Hook pada Webhook Settlement | Aiman | Laravel API (`retribusi-api`) | Event Listener status `PAID` pemicu dispatch job TTE | **READY** |
| **[AIMAN-041]** | Migrasi Skema DB: `signed_documents`, `tte_logs`, `asset_tokens` | Aiman | MariaDB 10.6.23 | DDL schema pencatatan hash berkas, sign status, token aset | **READY** |
| **[AIMAN-042]** | Integrasi Portal Verifikasi Publik E-Registry | Aiman | Laravel Controller / Frontend | Route publik `verify.baubaukota.go.id/doc/{number}` | **READY** |
| **[AIMAN-043]** | Update UI Card Transaksi & Tombol Download PDF Ber-TTE | Aiman | React PWA (Mobile & Admin) | Tombol interaktif unduh berkas & integrasi kirim WhatsApp | **READY** |
| **[HERMES-010]**| Konfigurasi Supervisor Worker Queue `tte_pending_jobs` | Hermes | Host VPS (157.10.252.74) | Worker daemon Redis pemroses antrean TTE (8 threads) | **READY** |
| **[HERMES-011]**| Telemetry Latensi API BSrE & Fallback Circuit Breaker | Hermes | Autonomous SRE | Watchdog polling RTT gateway BSrE (ambang batas 3.000 ms) | **READY** |
| **[HERMES-012]**| Immutable Audit Log Protection & PDF Encrypted Storage | Hermes | SRE Security Guard | Enforce revoke update/delete & sinkronisasi snapshot cadangan | **READY** |

---

## 3. Arsitektur Queue & Siklus Pemrosesan Asinkron

```
[ Warga Bayar via Bank/QRIS ]
             │
             ▼
[ Webhook Settlement Bank ] ───(SINKRON: <800 ms)───► [ lockForUpdate & Catat NTPD ]
                                                                 │
                                                      Status Transaksi = LUNAS (Kasda Sah)
                                                                 │
                                                       Dispatch Event Job
                                                                 ▼
                                                  [ Redis Queue: tte_pending_jobs ]
                                                                 │
                                                  Supervisor Worker (8 Daemon Threads)
                                                                 ▼
                                                    [ Generate PDF/A-1b Stream ]
                                                                 │
                                                        Hit API BSrE H2H
                                                     (SHA-256 + Sign Request)
                                                                 │
                                         ┌───────────────────────┴───────────────────────┐
                                         ▼                                               ▼
                                 [ Sukses (HTTP 200) ]                        [ Gagal / Timeout / 5xx ]
                                         │                                               │
                          Simpan Hash di `signed_documents`                   Exponential Backoff Retry
                                         │                                    (Interval: 1m, 5m, 15m)
                          Notifikasi Ready ke Warga (WA & PWA)                           │
                                         │                                    Jika 3x Tetap Gagal:
                                         ▼                                               ▼
                          Tampil di E-Registry & Public Verify               Sematkan "Kode Numerik OPD" +
                                                                             Segel Institusi Cadangan
```

### A. Konfigurasi Redis & Supervisor Worker
- **Queue Name**: `tte_pending_jobs`
- **Supervisor Config Path**: `/etc/supervisor/conf.d/mpad-tte-worker.conf`
- **Toleransi Kegagalan (Retry Policy)**:
  - Maksimal percobaan: 3 kali.
  - Delay berjenjang: 60 detik, 300 detik, 900 detik.
  - Jika server BSrE mengalami pemeliharaan (*maintenance*), status pembayaran kas daerah **tetap sah dan final**, tidak dibatalkan.

---

## 4. Rencana Skema Database Tambahan (DDL Migration)

```sql
-- 1. Tabel Registrasi Dokumen Ber-TTE
CREATE TABLE IF NOT EXISTS signed_documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(100) NOT NULL UNIQUE,
    document_type ENUM('SSPD', 'SSRD', 'STTS', 'SIPAB', 'SIPA', 'SKRD', 'SKPD') NOT NULL,
    bill_id BIGINT UNSIGNED NOT NULL,
    opd_id INT UNSIGNED NOT NULL,
    signer_name VARCHAR(255) NOT NULL,
    signer_nip VARCHAR(50) NULL,
    signer_role VARCHAR(100) NOT NULL,
    signature_type ENUM('ELECTRONIC_SEAL', 'PERSONAL_TTE', 'NUMERIC_TOKEN_TRANSITION') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    sha256_checksum CHAR(64) NOT NULL,
    verification_url VARCHAR(500) NOT NULL,
    signed_at TIMESTAMP NOT NULL,
    status ENUM('VALID', 'REVOKED') DEFAULT 'VALID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bill (bill_id),
    INDEX idx_doc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Audit Trail Integrasi BSrE
CREATE TABLE IF NOT EXISTS tte_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(100) NOT NULL,
    request_payload JSON NOT NULL,
    response_code INT NOT NULL,
    response_body JSON NOT NULL,
    duration_ms INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_num (document_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Token Registrasi Aset Khusus Transisi
CREATE TABLE IF NOT EXISTS asset_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    token_code VARCHAR(32) NOT NULL UNIQUE,
    opd_code VARCHAR(20) NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(100) NOT NULL,
    tenant_identity VARCHAR(100) NOT NULL,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Poin Strategis & Pertanyaan Kunci untuk Rapat Zoom BSrE

1. **Penyematan Segel Elektronik Otomatis (Server-to-Server / Seal)**:
   - *Poin*: Transaksi pembayaran pajak & retribusi berlangsung 24 jam secara masal pasca-bayar perbankan. M-PAD memerlukan API Segel Elektronik Instansi (*Server-to-Server*) yang dapat dipanggil otomatis oleh webhook sistem tanpa meminta PIN/Passphrase manual manusia.
   - *Pertanyaan*: *"Apakah sertifikat Segel Elektronik Pemkot Baubau dapat langsung diterbitkan untuk skema Auto-Sign tanpa antarmuka pengetikan passphrase per lembar dokumen?"*

2. **Kapasitas Throughput & Kuota Sandbox**:
   - *Poin*: Pada musim jatuh tempo PBB-P2 dan PBJT (Agustus-September), volume transaksi dapat mencapai ratusan transaksi per menit.
   - *Pertanyaan*: *"Berapa batas rate limit (request/second) dan batas concurrent connection yang dialokasikan BSrE pada server staging dan produksi?"*

3. **Format Naskah & Visual Stamp**:
   - *Poin*: M-PAD telah menstandarkan visual stamp berupa QR Code ukuran 2.5 x 2.5 cm di pojok kanan bawah dokumen PDF/A-1b yang menautkan langsung ke `verify.baubaukota.go.id`.
   - *Pertanyaan*: *"Apakah BSrE memiliki rekomendasi koordinat letak visual signature (X, Y) atau apakah sepenuhnya diserahkan kepada tata letak blade view daerah?"*

4. **Jalur Asistensi Penerbitan Sertifikat TTE Kepala OPD**:
   - *Poin*: Selain segel instansi, naskah perikatan aset memerlukan TTE Personal Kepala OPD (Kadis PUPR, BPKAD, Disperindag, Dishub).
   - *Permohonan*: *"Mohon dibukakan asistensi jalur percepatan penerbitan sertifikat TTE bagi para Kepala OPD Pemkot Baubau melalui Diskominfo Kota Baubau."*
