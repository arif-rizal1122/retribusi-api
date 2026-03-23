---
name: GitHub Project Manager
description: Panduan dan script untuk mengelola GitHub Project (V2), sinkronisasi item, dan pembaruan status issue/draft secara otomatis menggunakan GitHub CLI (gh).
---

# GitHub Project Manager Skill

Skill ini dirancang untuk mempermudah pengelolaan **GitHub Project Board** yang digunakan dalam ekosistem M-PAD (Kota Baubau). Fokus utamanya adalah sinkronisasi otomatis antara perkembangan lokal dengan status di board GitHub.

## 1. Konfigurasi Proyek
Proyek utama yang dikelola adalah:
- **Project Number:** 3
- **Project ID:** `PVT_kwHOAPm_Ts4BPIUY`
- **Owner:** `muhdanfyan`
- **Field Title ID:** `PVTF_lAHOAPm_Ts4BPIUYzg9oT5E`

## 2. Command Utama (GitHub CLI)

### Melihat Item Proyek
```bash
gh project item-list 3 --owner muhdanfyan --format json
```

### Mengedit Draft Issue (Title & Body)
Gunakan ID dengan prefix `DI_`:
```bash
gh project item-edit --id DI_XXX --project-id PVT_kwHOAPm_Ts4BPIUY --title "New Title" --body "New Body"
```

### Mengedit Regular Issue
Gunakan `gh issue edit`:
```bash
gh issue edit <number> --repo <owner/repo> --title "New Title" --body "New Body"
```

## 3. Sinkronisasi Otomatis (Rebranding/Updating)
Terdapat script Python pendukung di folder `scripts/sync_project.py` yang melakukan penggantian teks (seperti "SIPANDA" -> "Mpad") secara massal pada seluruh item di board.

### Cara Menjalankan Sinkronisasi:
1. Pastikan Anda sudah login via `gh auth login`.
2. Jalankan script:
```bash
python3 .agent/skills/gh_project_manager/scripts/sync_project.py
```

## 4. Best Practices
- **Atomic Updates:** Lakukan sinkronisasi setiap kali ada perubahan nama sistem atau milestone besar.
- **Verification:** Selalu jalankan `gh project item-list` setelah update untuk memastikan data telah tersinkron.
- **Mapping:** Pastikan pemetaan antara status lokal (misal: "Done" di `todo-list.md`) selaras dengan kolom "✅ Done" di GitHub.
