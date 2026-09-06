---
name: Systemic Resilience & Fatal Scenario Mitigation
description: Panduan mitigasi kegagalan sistemik arsitektur/jaringan, pencegahan 5 skenario fatal operasional lapangan, dan SOP ketahanan transaksi keuangan M-PAD Kota Baubau.
---

# 🛡️ Systemic Resilience & Fatal Scenario Mitigation
## Mitigasi Kegagalan Sistemik & Pencegahan 5 Skenario Fatal Ekosistem M-PAD

Skill ini menetapkan protokol arsitektur dan perlindungan transaksi tingkat tinggi (*mission-critical*) untuk menjamin ketersediaan layanan Satu Pintu Pendapatan Daerah Kota Baubau, mencegah temuan audit BPK RI, pelanggaran UU PDP No. 27/2022, serta kegagalan finansial akibat sengketa perbankan (SLA BI).

---

## 1. 🏗️ Mitigasi Kegagalan Sistemik (Arsitektur & Jaringan)

### A. Idempotency Key pada Webhook Callback Per Bank
* **Skenario Risiko**: Gangguan jaringan kosmetik menyebabkan server M-PAD lambat merespons HTTP 200 ke bank. Bank mengirimkan callback pelunasan berulang kali (*retry storm*), memicu duplikasi pencatatan jurnal pendapatan kasda (akun 4.1.x) berganda.
* **Standar Implementasi M-PAD**:
  1. Validasi gabungan unik `bank_id + transaction_id` (atau `external_id` / `reference_number`) sebagai *Idempotency Key* di tabel `snap_idempotency_keys` / `bank_transaction_logs`.
  2. Eksekusi pembaruan status di dalam blok transaksi database dengan **`lockForUpdate()`**:
     ```php
     DB::transaction(function () use ($billId, $referenceNo) {
         $bill = Bill::where('id', $billId)->lockForUpdate()->first();
         if ($bill->status === 'paid') {
             return response()->json(['responseCode' => '2002500', 'responseMessage' => 'Success (Already Paid)']);
         }
         // Proses pelunasan...
     });
     ```
  3. Jika transaksi sudah berstatus *Paid*, abaikan proses penjurnalan ulang dan langsung kembalikan respons sukses ke bank.

### B. Asynchronous Message Broker (Pemisahan Core Payment vs Cetak/TTE)
* **Skenario Risiko**: Saat puncak jatuh tempo pembayaran PBB atau PBJT, ribuan warga membayar serentak. Komputasi sinkron untuk hitung denda JIT, penggabungan PDF TTE BSrE, dan pengiriman notifikasi pesan mengakibatkan kehabisan worker (*Server Crash* / 504 Gateway Timeout).
* **Standar Implementasi M-PAD**:
  1. **Core Payment Respon Seketika**: Callback bank direspons HTTP 200 instan (< 1.5 detik) setelah status tagihan di database terkunci *Paid*.
  2. **Background Queue Engine**: Proses berat didelegasikan ke antrean asinkron (Redis Queue / Laravel Jobs):
     - `GenerateSignedPdfJob`: Pembuatan PDF SKRD/bukti bayar dengan QR TTE BSrE.
     - `SendWhatsAppReceiptJob`: Pengiriman pesan notifikasi kuitansi digital ke Wajib Pajak.

### C. Failover Offline-First Mode pada Aplikasi Lapangan (PWA Local Cache)
* **Skenario Risiko**: Petugas juru parkir, pengawas pasar, atau tim uji petik bekerja di area blank spot (pesisir/pasar tradisional). Aplikasi terputus, *force close*, dan input formulir atau foto koordinat hilang.
* **Standar Implementasi M-PAD**:
  1. Pemanfaatan Service Worker & IndexedDB pada aplikasi `retribusi-petugas`.
  2. Seluruh isian formulir, foto kamera, dan titik koordinat GPS disimpan secara lokal terlebih dahulu (`local_status = 'pending_sync'`).
  3. Background sync otomatis dipicu saat browser/aplikasi mendeteksi event `window.addEventListener('online', ...)`.

---

## 2. 🚨 Pencegahan 5 Skenario Fatal di Lapangan

| No | Skenario Fatal | Dampak Risiko | Standar Pola Yang Benar (Best Practice) |
|---|---|---|---|
| **1** | **Menampilkan NIK/PII Utuh di String Nomor VA** | Pelanggaran **UU PDP No. 27/2022** (Pidana kebocoran data pribadi pada struk/ATM). | **Skema Hybrid**: Nomor VA dikunci menggunakan ID Transaksi unik berbasis database (primary key acak 7–10 digit). Relasi NIK disimpan dengan enkripsi AES-256 di memori server internal Bapenda yang aman. |
| **2** | **Penundaan Eksekusi AFT (Automatic Fund Transfer)** | Gagal debet massal karena saldo operasional merchant keburu habis dipakai kulakan/supplier. | **Eksekusi Real-Time**: Perintah AFT Bank Sultra wajib dieksekusi dalam hitungan detik setelah notifikasi pembayaran QRIS/Transfer sukses terdeteksi perbankan. |
| **3** | **Penjurnalan 1-Lapis Langsung ke Kasda** | **Temuan Audit BPK RI** (Selisih kas, uang fisik masih mengendap di escrow bank). | **Jurnal 2-Tahap**: <br>• *Jurnal 1 (Real-time)*: Debit Rekening Penampungan & Kredit Pendapatan PAD.<br>• *Jurnal 2 (Batch Pukul 17.00 WITA)*: Debit Kas Daerah & Kredit Rekening Penampungan. |
| **4** | **Flag Konfigurasi Environment Produksi Salah** | **Total Outage**: Panjang nomor VA pecah (25 digit) sehingga warga gagal bayar massal. | **Pre-Flight Check Validasi `.env`**: Kunci panjang digit nomor VA standar (15 digit: 8 digit partnerServiceId + 7 digit customerNo). Cegah default fallback yang tidak teruji. |
| **5** | **Pengaduan Konsumen Melewati Batas BI (>60 Hari)** | Kerugian finansial daerah (Kompensasi sengketa dibebankan penuh ke Pemkot Baubau). | **Auto-Ticketing WhatsApp**: Aduan kegagalan bayar wajib menghasilkan tiket pengaduan instan ke akun admin OPD pengampu dengan target resolusi SLA maksimal **20 hari kerja**. |

---

## 3. 🔍 Checklist Verifikasi Pra-Rilis (Pre-Flight Checklist)

Sebelum melakukan deployment atau merge fitur finansial:
- [ ] Uji *Idempotency*: Kirim 5 payload callback bank identik secara paralel (`Promise.all`), pastikan hanya 1 record pembayaran yang tercatat.
- [ ] Cek Masking NIK: Pastikan nomor Virtual Account yang digenerate sama sekali tidak mengandung substring 16 digit NIK Wajib Pajak.
- [ ] Cek Durasi Eksekusi Endpoint Callback: Pastikan waktu respons webhook bank $\le 1.5$ detik (tanpa blocking proses render PDF TTE).
- [ ] Validasi Rekening Penampungan: Pastikan jurnal harian membedakan saldo escrow bank vs mutasi efektif kasda.
- [ ] Cek Auto-Ticket SLA: Uji simulasi aduan transaksi gagal, pastikan notifikasi peringatan SLA terkirim ke WhatsApp petugas penanggung jawab.
