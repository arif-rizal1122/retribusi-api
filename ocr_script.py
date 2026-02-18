import os
import re
import subprocess
import sys

# Configuration
SOURCE_DIR = "/Users/pondokit/Herd/retribusi-api/DATA PAJAK/Perwali Tata Cara Pemungutan PDRD nomor 58 tahun 2024/Perwali Tata Cara Pemungutan PDRD nomor 58 tahun 2024_.pdf/"
OUTPUT_FILE = "/Users/pondokit/.gemini/antigravity/brain/53088d3d-69c8-4cb5-8e80-f6c173d4d2bc/Perwali_58_2024_Full_Text.md"

def natural_sort_key(s):
    return [int(text) if text.isdigit() else text.lower()
            for text in re.split('([0-9]+)', s)]

def main():
    if not os.path.exists(SOURCE_DIR):
        print(f"Error: Directory not found: {SOURCE_DIR}")
        sys.exit(1)

    files = [f for f in os.listdir(SOURCE_DIR) if f.lower().endswith('.png')]
    files.sort(key=natural_sort_key)

    total_files = len(files)
    print(f"Found {total_files} images to process.")

    with open(OUTPUT_FILE, 'w', encoding='utf-8') as outfile:
        outfile.write("# Perwali Tata Cara Pemungutan PDRD Nomor 58 Tahun 2024\n\n")

        for i, filename in enumerate(files):
            input_path = os.path.join(SOURCE_DIR, filename)
            
            if (i+1) % 10 == 0:
                print(f"Processing {i+1}/{total_files}: {filename}...")

            try:
                # Attempt to use Indonesian language ('ind')
                # If tesseract-lang is not installed, this might fail or fallback.
                # We'll use a small trick: check if 'ind' is in tesseract --list-langs
                # If not, use 'eng' (default)
                
                # Check langs (only once preferably, but here is fine)
                # check_langs = subprocess.run(['tesseract', '--list-langs'], capture_output=True, text=True)
                # has_ind = 'ind' in check_langs.stdout
                
                # For simplicity, let's try 'ind' and catch stderr if it complains
                
                result = subprocess.run(['tesseract', input_path, 'stdout'], capture_output=True, text=True)
                
                if result.returncode != 0:
                    print(f"Error processing {filename}: {result.stderr}")

                text = result.stdout.strip()

                outfile.write(f"## Page {filename}\n\n")
                outfile.write(text)
                outfile.write("\n\n---\n\n")

            except Exception as e:
                print(f"Error processing {filename}: {e}")
                outfile.write(f"## Page {filename}\n\n")
                outfile.write(f"Error extracting text: {e}\n\n")
                outfile.write("\n\n---\n\n")

    print(f"Done! Saved to {OUTPUT_FILE}")

if __name__ == "__main__":
    main()
