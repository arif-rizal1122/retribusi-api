#!/bin/bash

# Define paths to all repositories
REPOS=(
    "/Users/pondokit/Herd/retribusi-api"
    "/Users/pondokit/Herd/retribusi-admin"
    "/Users/pondokit/Herd/retribusi-petugas"
    "/Users/pondokit/Herd/retribusi-mobile"
)

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Omni Workspace Synchronization & Deployment ===${NC}\n"

# 1. Ask for Commit Details using Conventional Commits standard
echo -e "${YELLOW}Silakan pilih tipe perubahan (Commit Type):${NC}"
echo "1) feat     : Fitur baru"
echo "2) fix      : Perbaikan bug"
echo "3) docs     : Hanya perubahan dokumentasi"
echo "4) style    : Perubahan yang tidak mempengaruhi logika (white-space, formatting, missing semi-colons, dll)"
echo "5) refactor : Perubahan kode yang bukan memperbaiki bug atau menambah fitur"
echo "6) perf     : Perubahan kode yang meningkatkan performa"
echo "7) test     : Menambah atau memperbaiki testing"
echo "8) chore    : Perubahan pada proses build atau tools bantuan (seperti dokumentasi generation)"

read -p "Pilih angka (1-8) [default: 1]: " type_choice

case $type_choice in
    2) commit_type="fix" ;;
    3) commit_type="docs" ;;
    4) commit_type="style" ;;
    5) commit_type="refactor" ;;
    6) commit_type="perf" ;;
    7) commit_type="test" ;;
    8) commit_type="chore" ;;
    *) commit_type="feat" ;;
esac

echo ""
read -p "Masukkan scope/komponen yang diubah (opsional, misal: auth, ui, api): " commit_scope
if [ -n "$commit_scope" ]; then
    commit_scope="($commit_scope)"
fi

echo ""
read -p "Masukkan deskripsi singkat perubahan (wajib, gunakan bahasa imperatif): " commit_desc
if [ -z "$commit_desc" ]; then
    echo "Deskripsi tidak boleh kosong. Menggunakan 'update'."
    commit_desc="update"
fi

# Format the final commit message
COMMIT_MESSAGE="${commit_type}${commit_scope}: ${commit_desc}"
echo -e "\n${GREEN}Commit message yang akan digunakan:${NC} ${COMMIT_MESSAGE}\n"

read -p "Lanjutkan proses sinkronisasi? (y/n) [default: y]: " confirm
if [[ "$confirm" == "n" || "$confirm" == "N" ]]; then
    echo "Dibatalkan."
    exit 0
fi

echo -e "\n${BLUE}Memulai sinkronisasi...${NC}"

# Iterate over repositories to commit and push
for repo in "${REPOS[@]}"; do
    if [ -d "$repo" ]; then
        echo -e "\n${YELLOW}>>> Memproses $repo...${NC}"
        cd "$repo" || continue
        
        # Check if there are changes
        if [[ $(git status --porcelain) ]]; then
            git add .
            git commit -m "$COMMIT_MESSAGE"
            git push origin main
            echo -e "${GREEN}Berhasil di-push.${NC}"
        else
            echo "Tidak ada perubahan, dilewati."
        fi
    else
        echo -e "\n${RED}>>> Direktori $repo tidak ditemukan, dilewati.${NC}"
    fi
done

echo -e "\n${BLUE}=== Sinkronisasi GitHub Selesai ===${NC}"
echo -e "Untuk melakukan deployment ke VPS Production, gunakan perintah dari .agent/workflows/sync.md"
