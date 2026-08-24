# 🛡️ Analisis Risiko & Dampak Perbaikan Keamanan

Berdasarkan hasil [Penetration Test](./testing-reports/13_Penetration_Test_20260225_202408.md), berikut adalah analisis mendalam mengenai apa yang perlu diperbaiki, dampak positifnya, dan risiko teknis yang mungkin timbul selama proses perbaikan.

---

## 1. Perbaikan CORS Misconfiguration (Critical)
**Masalah:** API saat ini menerima request dari origin mana pun (termasuk `https://evil-hacker.com`).

- **Apa yang akan terjadi:** Kita akan memperketat Nginx & Laravel agar hanya menerima request dari domain `*.sipanda.online` dan `localhost`.
- **Dampak Positif:** Menghilangkan risiko serangan CSRF dan pencurian token dari situs jahat.
- **Risiko Teknikal:** 
  > [!WARNING]
  > Jika ada subdomain baru atau port lokal (misal port 3004) yang lupa didaftarkan, frontend akan **macet total** dengan error "CORS Blocked".
- **Mitigasi:** Melakukan audit menyeluruh terhadap semua domain yang digunakan sebelum mendeploy konfigurasi baru.

---

## 2. Perbaikan Privilege Escalation (Citizen vs Admin API)
**Masalah:** Akun Warga (Citizen) mencoba mengakses `/api/users` (Admin only) dan sistem memberikan respons 500 (seharusnya 403 Forbidden).

- **Apa yang akan terjadi:** Kita akan menambahkan Middleware Role Check pada rute-rute admin.
- **Dampak Positif:** Mencegah warga melihat data user lain atau data internal pegawai BAPENDA.
- **Risiko Teknikal:** 
  - Jika ada fitur warga yang membutuhkan data user (misal: fitur "Cari Petugas"), fitur tersebut bisa ikut terblokir jika tidak dipisahkan endpoint-nya.
- **Mitigasi:** Verifikasi semua alur kerja aplikasi Mobile sebelum memperketat rute.

---

## 3. Perbaikan XSS pada Respons Registrasi
**Masalah:** Data yang dikirim saat daftar (misal Nama) dipantulkan kembali ke respons tanpa sanitasi.

- **Apa yang akan terjadi:** Melakukan enkoding karakter khusus (seperti `<` menjadi `&lt;`) pada semua data input yang dikembalikan ke sistem.
- **Dampak Positif:** Mencegah script jahat berjalan di browser user lain yang melihat data tersebut.
- **Risiko Teknikal:** Sangat rendah. Nama yang mengandung simbol mungkin akan terlihat aneh di database jika tidak di-decode dengan benar oleh frontend.
- **Mitigasi:** Gunakan fungsi `e()` atau `htmlspecialchars` di Laravel secara konsisten.

---

## 4. Perbaikan IDOR (Akses Billing)
**Masalah:** Warga bisa mencoba melihat tagihan (Bill) milik orang lain dengan mengganti nomor ID di URL.

- **Apa yang akan terjadi:** Menambahkan filter `where('user_id', auth()->id())` pada query pengambilan data bill.
- **Dampak Positif:** Menjamin kerahasiaan data finansial individu.
- **Risiko Teknikal:** 
  - Bisa mengganggu fungsi jika satu tagihan boleh dibayar oleh orang lain (misal: anak membayar tagihan orang tua).
- **Mitigasi:** Gunakan skema "Layanan Pihak Ketiga" jika fitur titip bayar memang diperlukan, daripada membiarkan akses ID terbuka.

---

## Kesimpulan Risiko Keseluruhan
Secara umum, risiko terbesar ada pada **Konfigurasi CORS**. Jika ini salah, seluruh aplikasi `admin.sipanda.online`, `mobile.sipanda.online`, dll, bisa berhenti berfungsi. Perbaikan lainnya bersifat internal logika bisnis dan resikonya relatif kecil (Low Risk).

> [!TIP]
> Saya menyarankan perbaikan dilakukan bertahap dimulai dari **Privilege Escalation** dan **IDOR** sebelum menyentuh **CORS** di level Nginx.
