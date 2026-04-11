# Panduan Implementasi: Modul Penugasan (To-Do List PetugasTasks)

**Tujuan:** Memberikan Pengawas kemampuan menugaskan Petugas mendatangi zona / wajib pajak pada tanggal tertentu. Mengukur kinerja operasional Petugas berdasarkan kecepatan penyelesaian tugas *(Turnaround time)*.

---

## 🏗️ 1. Skema Database & Migrasi (Backend)
1. **Perbaikan `database/migrations/xxxx_xx_xx_create_petugas_tasks_table.php`:**
   Pastikan tabelnya sukses di-_migrate_ (kemarin terkendala `table already exists` saat rollback step=1). Harus dipastikan migrasinya berjalan bersih (`php artisan migrate:refresh --path=...` jika perlu).
2. **Model `App\Models\PetugasTask`**:
   Relasikan ke `User` (berperan sebagai *creator* maupun *assignee*), `Zone`, dan `Taxpayer`.

---

## 🔗 2. Skema Backend API Controller
Endpoint CRUD diatur di `routes/api.php` dengan Auth Middleware:
1. `GET /api/petugas-tasks`: (Untuk List)
   - Jika role = admin, kembalikan semua tugas. (Termasuk Global Scope Role-Based Admin jika tipe retribusinya difilter).
   - Jika role = petugas, *HANYA kembalikan tugas `where('user_id', auth()->id())`*.
2. `POST /api/petugas-tasks`: (Admin-only). Admin _me-lempar_ tugas baru ke Petugas.
3. `PUT /api/petugas-tasks/{id}`: (Petugas-only). Petugas menandai `status = 'completed'`, lalu backend mengisi `completed_at = now()`.
4. `DELETE /api/petugas-tasks/{id}`: (Admin-only). Pembatalan tugas.

---

## 📱 3. Skema Frontend Pilihan/Mobile (`retribusi-petugas`)
Modifikasi di repositori sisi Petugas (Mobile-view Frontend):
1. **Botton Nav `src/components/layout/BottomNav.tsx`:** Tambahkan tab "Tugas" / "To-Do" untuk navigasi cepat.
2. **Halaman `src/pages/DaftarTugas.tsx`:**
   - Gunakan `api.get('/api/petugas-tasks')`.
   - Pisahkan View menjadi dua Tab UI: **"Tugas Menunggu"** dan **"Tugas Selesai"**.
   - Tambahkan pewarnaan pada peringatan `due_date`:
     - Merah: Status masih `pending` padahal `due_date` sudah lewat (Kinerja Telat).
     - Hijau: Status `completed`.
3. **Validasi (Aksi Selesai):** Saat petugas klik sebuah tombol centang ✅ di tugas, UI memanggil `api.put(/api/petugas-tasks/id, {status: 'completed'})` dan UI beralih seketika.

---

## 📊 4. Kalkulasi Kinerja Bulanan
Di `DashboardController.php`, hitung **Persentase Kepatuhan (Compliance Rate)** Petugas:
- `Total Tugas Selesai Tepat Waktu` = `whereNotNull('completed_at')->whereRaw('completed_at <= due_date')`
- `Kinerja Petugas (%)` = `(Selesai Tepat Waktu / Total Semua Tugasnya) * 100`.
Data ini dicerminkan pada tabel Modul *Field Force Analytics* di Admin Pengawas.
