---
name: Omni Deployment & Environment Sync Strategy
description: Skill komprehensif (Master Pipeline) yang menyatukan 6 pilar untuk meminimalisir error saat proses update, merge, deployment, dan sinkronisasi lintas repositori di setiap environment (Local, Staging, Production).
---

# 🚀 Omni Deployment & Environment Sync Strategy (Ecosystem Master Pipeline)

Skill ini adalah "Panglima Tertinggi" dalam siklus rilis. Gunakan skill ini setiap kali mengeksekusi integrasi cabang (Merge), pembaruan kode lintas server, atau perbaikan *bug regresi* setelah pengujian QA.

## 🏛️ 6 Pilar Sinkronisasi Bebas Error

### 1. Promote Only Verified (Test Driven Promotion)
- **Larangan Keras:** Dilarang melakukan komit *merge* dari `dev` ke `staging` atau dari `staging` ke `main` (Production) jika pengujian di server sebelumnya belum lolos uji.
- **SOP:** Selalu jalankan `test_api_crud.sh` dan rangkaian `stg5_document_integrity.php`. Jika satupun laporan di `testing/results/` menunjukkan "FAIL" atau "HTTP 500", deployment DIBATALKAN.

### 2. Prioritas Resolusi Konflik (The Staging Golden Rule)
- **Latar Belakang:** Seringkali commit pada `main` dimodifikasi diam-diam atau mengalami divergensi.
- **Resolusi:** Jika menemui *merge conflict* antara cabang `staging` dan `main`, selalu paksa penerimaan kode staging dengan perintah `git checkout --theirs`. Versi staging merupakan kode dengan performa fungsional yang paling terjamin.

### 3. Atomic Multi-Repo Sync (Staggered API-First Rule)
- Ekosistem retribusi memiliki 4 pilar repositori mandiri. Untuk mencegah *Breaking Changes* antar-service (karena frontend memanggil kolom/endpoint yang belum ada), siklus rilis **WAJIB** berurutan:
  1. `retribusi-api` (Modifikasi Data / Endpoint)
  2. `retribusi-admin` (Kendali Global / UI Makro)
  3. `retribusi-mobile` (Pelaporan Citizen Baru)
  4. `retribusi-petugas` (Konsumsi Ujung Paling Akhir)

### 4. Hard Sync untuk Cabang Divergen (VPS Protection)
- Fitur CI/CD pada lingkungan staging maupun production sangat rentan gagal karena "divergent branches" yang menghalangi `git pull` tanpa `--rebase`.
- **Mitigasi di VPS:** 
  Jangan perbaiki menggunakan *merge* buta di VPS. Murni gunakan **Hard Reset**:
  ```bash
  git fetch origin
  git reset --hard origin/<nama-branch>
  ```
  Langkah ini memastikan kode di server persis 100% dengan repositori asal (GitHub).

### 5. Flushing PHP-FPM (Memory Cache Invalidation)
- Mengubah `.env` (misal merotasi *DB_PASSWORD* atau Token Gateway) tidak langsung aktif walau sudah `php artisan config:clear`.
- Memori PHP-FPM sangat agresif. Jika aplikasi merespons Otorisasi Ditolak pasca-deploy padahal kredensial di file config sudah akurat, memori PHP-FPM lah penyebabnya.
- **Mitigasi:** Paksa inisialisasi ulang pool process dengan *systemctl*:
  ```bash
  sudo systemctl reload php8.3-fpm
  ```

### 6. Automation CI/CD Ketat (Defensive Actions)
- Hindari parameter lepas (`@master` atau tag fleksibel) pada GitHub Action Workflow. Format tidak stabil dari pihak ketiga dapat merusak pipeline integrasi berkelanjutan.
- **Standar Format `deploy.yml`:**
  - `uses: appleboy/ssh-action@v1.2.0`
  - Tambahkan parameter persisten: `port: ${{ secrets.VPS_PORT }}`
  - Jastifikasi rilis file frontend dengan: `source: "dist/*"` dan `strip_components: 1`.

---

## 🛠️ Mitigasi Situasional
- **"Access denied for user '@'localhost' (using password: NO)"**: Ini merupakan efek samping pilar 5. Bersihkan kutip ganda/satu dari nilai `.env`, hapus *cache artisan*, lalu reload PHP-FPM pool.
- **"Permission denied on /storage/logs"**: Eksekusi perintah CI/CD dengan *user* reguler dapat menyebabkan kepemilikan file berpindah. Eksekusi `chown -R www-data:www-data storage/` sebelum melempar aplikasi ke *public*.
