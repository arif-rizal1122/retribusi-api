---
name: Omni Documentation Sync & Intelligence
description: Mengelola siklus hidup dokumentasi M-PAD, memperbarui pemahaman fungsional (Master Mapping), dan mensinkronisasikan aset ke Google Drive.
---

# 📚 Omni Documentation Sync & Intelligence

Skill ini bertanggung jawab atas **Integritas Kontekstual** dokumentasi M-PAD. Gunakan skill ini setiap kali ada perubahan arsitektur, penambahan fitur signifikan, atau kebutuhan backup ke Google Drive.

## 🧠 Filosofi "Understanding First"
**DILARANG** melakukan sinkronisasi ke Drive tanpa memperbarui pemahaman internal terlebih dahulu. Sebelum mengunggah, Agen wajib:
1.  **Scan Workspace**: Mencari semua file `.md` terbaru.
2.  **Update Master Mapping**: Memperbarui `docs/98_pemetaan_fungsi/MAP_FUNGSIONAL_DOKUMEN.md`.
3.  **Update Mega Doc**: Memperbarui `docs/99_umum_system/MEGA_DOCUMENTATION.md` (Opsional jika ukuran terlalu besar).

## 🛠️ Protokol Sinkronisasi (Rclone)

| Remote Name | Target Path | Repo Source |
| :--- | :--- | :--- |
| `muhdanfyan:` | `mpad/documentation/api/` | `retribusi-api/docs/` |
| `muhdanfyan:` | `mpad/documentation/admin/` | `retribusi-admin/docs/` |
| `muhdanfyan:` | `mpad/documentation/petugas/` | `retribusi-petugas/docs/` |

### Prosedur Unggah:
1.  Jalankan script `update_docs_understanding.sh`.
2.  Verifikasi status remote: `/usr/local/bin/rclone listremotes`.
3.  Eksekusi Copy: `/usr/local/bin/rclone copy [source] [remote]:[path]`.

## ⚠️ Mitigasi Masalah Token (Expired)
Jika rclone mengembalikan error `invalid_grant` atau `token expired`:
1.  **STOP** proses otomatis.
2.  Minta USER menjalankan: `/usr/local/bin/rclone config reconnect muhdanfyan:`.
3.  Tunggu konfirmasi USER sebelum mencoba lagi.

## 📁 Struktur Pemetaan Fungsi
Selalu klasifikasikan dokumen baru ke dalam salah satu kategori berikut dalam `MAP_FUNGSIONAL_DOKUMEN.md`:
- **Regulasi & Hukum** (Perda/Perwali)
- **Arsitektur & Database** (Schema/Backend)
- **Proses Bisnis & Penagihan** (Workflow/Payment)
- **Infrastruktur & API** (Server/Routes)
- **Quality Assurance** (Testing/Audit)
- **Agentic AI** (Skills/Rules)
