from PIL import Image
import os

source_dir = "/Users/pondokit/Herd/retribusi-api/Logo MPAD"
destinations = {
    "MPAD Admin.png": "/Users/pondokit/Herd/retribusi-admin/public/mitra-logo.png",
    "MPAD Mobile.png": "/Users/pondokit/Herd/retribusi-mobile/public/mitra-logo.png",
    "MPAD Petugas.png": "/Users/pondokit/Herd/retribusi-petugas/public/mitra-logo.png",
    "Logo MPAD NoBG.png": "/Users/pondokit/Herd/retribusi-api/public/mitra-logo.png"
}

for filename, dest_path in destinations.items():
    src_path = os.path.join(source_dir, filename)
    if os.path.exists(src_path):
        try:
            img = Image.open(src_path).convert("RGBA")
            
            # Make sure it's cropped/trimmed of white borders if any? 
            # Or just resize it to a max of 512x512
            img.thumbnail((512, 512), Image.Resampling.LANCZOS)
            
            img.save(dest_path, format="PNG", optimize=True, quality=80)
            print(f"Compressed and saved {filename} to {dest_path}")
        except Exception as e:
            print(f"Error processing {filename}: {e}")
    else:
        print(f"File not found: {src_path}")
