# 📋 Kredensial & Akses Leantime Project Management (M-PAD)

Dokumen ini mencatat informasi kredensial, konfigurasi database, dan akses server untuk sistem manajemen proyek **Leantime** (`leantime.sarjanakomputer.id`) yang terintegrasi dengan ekosistem M-PAD (Kota Baubau).

---

## 1. Akses Web Leantime

* **URL Production**: [https://leantime.sarjanakomputer.id](https://leantime.sarjanakomputer.id) (atau [http://leantime.sarjanakomputer.id](http://leantime.sarjanakomputer.id))
* **URL Local (Docker)**: `http://localhost:8080`
* **Akun Administrator**:
  * **Email / Username**: `admin@sarjanakomputer.id`
  * **Password**: `Admin123!`

---

## 2. Akses Database (MySQL / MariaDB `skom`)

Database Leantime berjalan di dalam container Docker di VPS Aiman / Pondok:

| Parameter | Lingkungan VPS Production | Lingkungan Lokal Docker |
| :--- | :--- | :--- |
| **Container Name** | `pisantri-db` | `leantime-mysql-local` |
| **Host / IP** | `127.0.0.1` / `172.17.0.1` | `127.0.0.1` |
| **Port** | `3307` | `3307` (mapped ke `3306`) |
| **Database Name** | `skom` | `skom` |
| **Username** | `leantime` | `leantime` / `root` |
| **Password** | `L3ant1m3Skom!` *(atau `L34nt1m3_2026`)* | `root123` / `L34nt1m3_2026` |

### Perintah Query Langsung ke Database VPS:
```bash
ssh -i /Users/pondokit/Herd/aiman/pi.pem pondokinformatika@103.150.196.183 "docker exec pisantri-db mysql -uleantime -p'L3ant1m3Skom!' skom -e 'SELECT id, projectId, headline, status FROM zp_tickets WHERE projectId=1;'"
```

---

## 3. Akses Server VPS (Host Leantime & Aiman)

* **Provider**: Biznet NEO Cloud (Primary VPS: Pondok)
* **IP Server**: `103.150.196.183`
* **Username**: `pondokinformatika`
* **SSH Key Path**: `/Users/pondokit/Herd/aiman/pi.pem` (atau `~/.ssh/id_ed25519`)
* **Password Console**: `Piblajar2020`
* **Direktori Leantime di VPS**: `/home/pondokinformatika/leantime.skom`
* **Direktori Leantime Lokal**: `/Users/pondokit/Herd/leantime.skom`

---

## 4. Mapping Project ID M-PAD di Leantime (`zp_projects`)

| Project ID | Nama Proyek | Cakupan Modul |
| :---: | :--- | :--- |
| **1** | `M-PAD (Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)` | Master Board, Kebijakan, API, Bank H2H, Regulasi |
| **2** | `M-PAD Launch Page` | Landing Page & Portal Informasi Publik |
| **3** | `M-PAD Mobile App` | Aplikasi Warga / Wajib Pajak (`retribusi-mobile`) |
| **4** | `M-PAD Admin Web` | Dashboard Admin & OPD (`retribusi-admin`) |
| **5** | `M-PAD Petugas App` | Aplikasi Petugas Lapangan (`retribusi-petugas`) |

---

## 5. Definisi Kode Status Tiket Leantime (`zp_tickets.status`)

| Nilai Status | Label Sistem | Arti / Status |
| :---: | :--- | :--- |
| **`3`** | `status.new` | Tiket Baru / To Do |
| **`4`** | `status.in_progress` | Sedang Dikerjakan |
| **`1`** | `status.blocked` | Tertahan / Menunggu PKS atau Pihak Luar |
| **`2`** | `status.waiting_for_approval` | Menunggu Persetujuan / Review |
| **`0`** | `status.done` | Selesai / Terverifikasi |
| **`-1`** | `status.archived` | Diarsipkan |
