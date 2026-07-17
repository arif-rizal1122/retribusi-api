html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
md_file = '/Users/pondokit/Herd/retribusi-api/docs/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.md'

with open(html_file, 'r') as f:
    html_content = f.read()

# Replace specifically the Landasan Hukum ul with ol
html_content = html_content.replace(
"""<ul>
<li><strong>Undang-Undang Nomor 1 Tahun 2022""",
"""<ol>
<li><strong>Undang-Undang Nomor 1 Tahun 2022"""
)
html_content = html_content.replace(
"""terkait interkoneksi sistem perbankan.</li>
</ul>""",
"""terkait interkoneksi sistem perbankan.</li>
</ol>"""
)

with open(html_file, 'w') as f:
    f.write(html_content)

with open(md_file, 'r') as f:
    md_content = f.read()

md_content = md_content.replace(
"""*   **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
*   **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
*   **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
*   Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan.""",
"""1.  **Undang-Undang Nomor 1 Tahun 2022** tentang Hubungan Keuangan Antara Pemerintah Pusat dan Pemerintahan Daerah (HKPD).
2.  **Peraturan Daerah (Perda) Kota Baubau Nomor 1 Tahun 2024** tentang Pajak Daerah dan Retribusi Daerah (PDRD).
3.  **Peraturan Wali Kota Baubau Nomor 58 Tahun 2024** tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah.
4.  Standar Nasional Open API Pembayaran (SNAP) dari Bank Indonesia terkait interkoneksi sistem perbankan."""
)

with open(md_file, 'w') as f:
    f.write(md_content)

print("List fixed")
