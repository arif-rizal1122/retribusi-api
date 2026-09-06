import re

# 1. Update HTML file
html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
with open(html_file, 'r') as f:
    html_content = f.read()

new_table_html = """
            <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11pt;">
                <thead class="table-light">
                <tr>
                    <th scope="col" class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #f8f9fa;">No</th>
                    <th scope="col" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #f8f9fa;">Uraian / Kegiatan</th>
                    <th scope="col" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #f8f9fa;">Biaya</th>
                </tr>
                </thead>
                <tbody>
                <tr class="table-secondary">
                    <td colspan="3" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #e9ecef;"><strong>🏛️ A. Platform Admin BAPENDA (Web Dashboard)</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">1</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Dashboard Manajemen Data & Pengguna</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 30.000.000</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">2</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Modul Penetapan & Penagihan Pajak/Retribusi</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 25.000.000</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">3</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Modul Laporan & Dashboard Monitoring PAD</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 22.500.000</strong></td>
                </tr>
                <tr class="table-secondary">
                    <td colspan="3" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #e9ecef;"><strong>👥 B. Platform Petugas (Aplikasi Mobile)</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">4</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Aplikasi Petugas Lapangan (Pendataan & Verifikasi)</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 35.000.000</strong></td>
                </tr>
                <tr class="table-secondary">
                    <td colspan="3" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #e9ecef;"><strong>📱 C. Platform Masyarakat (Aplikasi Mobile)</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">5</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Aplikasi Mobile Wajib Pajak (Pembayaran & Informasi)</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 30.000.000</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">6</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Integrasi Pembayaran Digital (QRIS/VA/E-Wallet)</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 20.000.000</strong></td>
                </tr>
                <tr class="table-secondary">
                    <td colspan="3" style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #e9ecef;"><strong>⚙️ D. Infrastruktur & Dokumentasi</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">7</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Setup Server & Deployment</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 15.000.000</strong></td>
                </tr>
                <tr>
                    <td class="text-center" style="border: 1px solid #dfe3ee; padding: 8px 12px;">8</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">User Guide & Dokumentasi</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;"><strong>Rp 0</strong></td>
                </tr>
                <tr class="table-light">
                    <td colspan="2" class="text-end" style="border: 1px solid #dfe3ee; padding: 8px 12px; text-align: right; background-color: #f8f9fa;"><strong>Subtotal</strong></td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #f8f9fa;"><strong>Rp 177.500.000</strong></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end" style="border: 1px solid #dfe3ee; padding: 8px 12px; text-align: right;">PPN (12%)</td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px;">Rp 21.300.000</td>
                </tr>
                <tr class="total-row table-light">
                    <td colspan="2" class="text-end" style="border: 1px solid #dfe3ee; padding: 8px 12px; text-align: right; background-color: #f8f9fa;"><strong>Total Biaya</strong></td>
                    <td style="border: 1px solid #dfe3ee; padding: 8px 12px; background-color: #f8f9fa;"><strong style="color: #c0392b;">Rp 198.800.000</strong></td>
                </tr>
                </tbody>
            </table>
"""

new_html_content = re.sub(r'<table>.*?</table>', new_table_html, html_content, flags=re.DOTALL)
new_html_content = new_html_content.replace(
    "<p>Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia, berikut adalah estimasi pembiayaan untuk pengembangan perangkat lunak sistem terintegrasi tersebut:</p>",
    "<p>Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia, berikut adalah rincian anggaran yang dialokasikan untuk pengembangan 3 platform utama (Web Admin BAPENDA, Aplikasi Mobile Petugas, dan Aplikasi Mobile Masyarakat), serta integrasi pembayaran digital dan dokumentasi:</p>"
)

with open(html_file, 'w') as f:
    f.write(new_html_content)

# 2. Update MD file
md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'
with open(md_file, 'r') as f:
    md_content = f.read()

new_table_md = """| No | Uraian / Kegiatan | Biaya |
|:---:|---|---:|
| **A** | **Platform Admin BAPENDA (Web Dashboard)** | |
| 1 | Dashboard Manajemen Data & Pengguna | Rp 30.000.000 |
| 2 | Modul Penetapan & Penagihan Pajak/Retribusi | Rp 25.000.000 |
| 3 | Modul Laporan & Dashboard Monitoring PAD | Rp 22.500.000 |
| **B** | **Platform Petugas (Aplikasi Mobile)** | |
| 4 | Aplikasi Petugas Lapangan (Pendataan & Verifikasi) | Rp 35.000.000 |
| **C** | **Platform Masyarakat (Aplikasi Mobile)** | |
| 5 | Aplikasi Mobile Wajib Pajak (Pembayaran & Informasi) | Rp 30.000.000 |
| 6 | Integrasi Pembayaran Digital (QRIS/VA/E-Wallet) | Rp 20.000.000 |
| **D** | **Infrastruktur & Dokumentasi** | |
| 7 | Setup Server & Deployment | Rp 15.000.000 |
| 8 | User Guide & Dokumentasi | Rp 0 |
| | **Subtotal** | **Rp 177.500.000** |
| | **PPN (12%)** | **Rp 21.300.000** |
| | **Total Biaya** | **Rp 198.800.000** |"""

md_content = re.sub(r'\| No \| Kategori & Deskripsi Pekerjaan \| Total Biaya \(Rp\) \|.*?\| \| \*\*TOTAL KESELURUHAN\*\* \| \*\*173\.600\.000\*\* \|', new_table_md, md_content, flags=re.DOTALL)
md_content = md_content.replace(
    "Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia, berikut adalah estimasi pembiayaan untuk pengembangan perangkat lunak sistem terintegrasi tersebut:",
    "Berdasarkan penawaran implementasi dari CV Sarjana Komputer Indonesia, berikut adalah rincian anggaran yang dialokasikan untuk pengembangan 3 platform utama (Web Admin BAPENDA, Aplikasi Mobile Petugas, dan Aplikasi Mobile Masyarakat), serta integrasi pembayaran digital dan dokumentasi:"
)

with open(md_file, 'w') as f:
    f.write(md_content)

print("Updated successfully")
