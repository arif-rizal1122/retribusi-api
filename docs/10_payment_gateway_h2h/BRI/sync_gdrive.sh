#!/bin/bash

# Script untuk sinkronisasi folder BRI lokal dengan Google Drive Bapenda
# Google Drive Folder ID: 1j6h5Y2pFumHOU3fTvRr4XMppdkjkWPYg (Folder Titipan PAD Bapenda / bank-bank - BRI)

echo "Memulai sinkronisasi dari Google Drive ke lokal..."
/usr/local/bin/rclone copy gdrive: --drive-root-folder-id 1j6h5Y2pFumHOU3fTvRr4XMppdkjkWPYg "$(dirname "$0")" -v
echo "Sinkronisasi selesai!"
