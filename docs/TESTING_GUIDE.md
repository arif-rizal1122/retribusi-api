# 🧪 Panduan Pengujian (Testing Guide)

Dokumen ini adalah acuan untuk menjalankan dan memahami pengujian sistem M-PAD.

## 1. Akun Acuan Testing (Human Review)
| Peran (Role) | Kredensial (NIK/Email) | Password |
| :--- | :--- | :--- |
| **Super Admin** | `bapenda@baubaukota.go.id` | `password123` |
| **Wajib Pajak** | `1234567890123456` | `password123` |
| **Petugas** | `petugas@bapenda.go.id` | `password123` |

## 2. Instruksi Untuk AI Agent
Pusat kendali pengujian telah dialihkan ke **Agent Skills** untuk efisiensi token dan akurasi eksekusi:
- **Protokol E2E**: Aktifkan skill `omni_workspace_tester`.
- **Mitigasi Bug**: Aktifkan skill `qa_error_registry`.
- **Standar /noss**: Semua pengujian wajib menggunakan script di folder `testing/`.

## 3. Laporan Pengujian
Hasil eksekusi dapat dilihat di:
- `testing/results/` (Log mentah & Summary markdown).
- `docs/testing-reports/` (Audit Keamanan & V-Tax Parity).

