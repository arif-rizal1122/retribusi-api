#!/bin/bash

# Script untuk sinkronisasi folder BTN lokal dengan Google Drive Bapenda
# Google Drive Folder ID: 123TxbA9AanrdI4VvKWUh5GTqOFyHGZ6V (Folder Titipan PAD Bapenda / bank-bank)

echo "Memulai sinkronisasi dari lokal ke Google Drive..."
/usr/local/bin/rclone copy "$(dirname "$0")" gdrive:BTN --drive-root-folder-id 123TxbA9AanrdI4VvKWUh5GTqOFyHGZ6V -v
echo "Sinkronisasi selesai!"
