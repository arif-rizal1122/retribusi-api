---
name: "PBB & BPHTB UI/UX Logic Specialist"
description: "Panduan standar untuk menangani rendering UI/UX, input, dan integrasi H2H untuk form PBB (Pajak Bumi & Bangunan) dan BPHTB agar tidak membingungkan Wajib Pajak dan mencegah anomali data."
---

# PBB & BPHTB UI/UX Logic
**Rule: Zero Manual Input for H2H Data.**

## 1. PBB-P2
- **NOP Only**: 18-digit NOP is the sole required user input.
- **Readonly Metadata**: Force `readonly: true` on `Luas Bumi`, `Luas Bangunan`, `NJOP Bumi`, `NJOP Bangunan`, `NJOPTKP`, `Nama WP`, `Alamat Objek PBB`.
- **No User Fallback**: Never fallback H2H fields to `formData.name` or `formData.address`. Use literal `"Otomatis setelah Cek Data NOP"`.
- **Hide Unused**: Do not render non-readonly PBB metadata as `<input>`.

## 2. BPHTB
- **H2H Readonly (from NOP)**: Lock `NJOP Bumi`, `NJOP Bangunan`, `Luas Bumi`, `Luas Bangunan`, `Nama WP PBB`.
- **Manual Required Inputs**:
  - `NPOP` (Nilai Transaksi)
  - `Jenis Perolehan` (Determines NPOPTKP rule)
  - `Nama WP BPHTB` (New Owner)
- **Auto-Calculated**: `NPOPTKP` MUST be fetched from backend based on `Jenis Perolehan`. No manual input allowed.

## 3. Titik Lokasi & Foto (All)
- **Map Coordinates (Titik Lokasi)**: ALWAYS required for both PBB and BPHTB to map objects spatially (GIS). Must use the map picker.
- **Gambar/Foto Objek**: ALWAYS required as visual evidence of the property/transaction. Must provide upload inputs.

## Validation
- [ ] PBB form has NO manual input for Luas/NJOP.
- [ ] "Orang Lain" registration does not leak account owner's profile (`formData.name/address`).
- [ ] BPHTB strictly separates H2H data (readonly) vs Transaction data (input).
- [ ] NOP Inquiry must precede form submission.
- [ ] Location picker (titik map) and image upload (gambar) are rendered and required.
- [ ] Ref: `BAUBAU_REGULATORY_MASTER.md` for NPOPTKP/tariffs.
