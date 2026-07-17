html_file = '/Users/pondokit/Herd/visibaubau-4.0/old/dokumen/Dokumen_Perencanaan_Sistem_Pendapatan_Baubau.html'
with open(html_file, 'r') as f:
    content = f.read()

# Add CSS to shrink mermaid diagrams
css_rule = """
        .mermaid {
            text-align: center;
            margin: 15px auto;
        }
        .mermaid svg {
            max-width: 80% !important;
            height: auto !important;
        }
"""
if '.mermaid svg' not in content:
    content = content.replace("</style>", f"{css_rule}\n    </style>")

with open(html_file, 'w') as f:
    f.write(content)
print("CSS updated")
