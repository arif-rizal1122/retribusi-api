from PIL import Image
import numpy as np
import os

destinations = [
    "/Users/pondokit/Herd/retribusi-admin/public/mitra-logo.png",
    "/Users/pondokit/Herd/retribusi-mobile/public/mitra-logo.png",
    "/Users/pondokit/Herd/retribusi-petugas/public/mitra-logo.png",
    "/Users/pondokit/Herd/retribusi-api/public/mitra-logo.png"
]

for dest_path in destinations:
    img = Image.open(dest_path).convert("RGBA")
    data = np.array(img)
    h, w, c = data.shape
    
    black_pixels = 0
    for y in range(h):
        for x in range(w):
            r, g, b, a = data[y, x]
            if a > 0 and r < 30 and g < 30 and b < 30:
                black_pixels += 1
                
    print(f"Black pixels in {os.path.basename(os.path.dirname(os.path.dirname(dest_path)))} logo: {black_pixels}")
