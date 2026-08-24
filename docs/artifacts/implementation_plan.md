# Plan: Implement Partial Features & SPPT Online

This plan covers the implementation of features previously identified as "Partially Implemented" and the new request for an SPPT Online (PBB) generator.

## Proposed Changes

### [retribusi-api]
#### [NEW] [sppt.blade.php](file:///Users/pondokit/Herd/retribusi-api/resources/views/pdf/sppt.blade.php) [NEW]
- Create a professional SPPT (Surat Pemberitahuan Pajak Terutang) template for PBB-P2.
- Layout: Header BAPENDA, NOP, Taxpayer & Object details, NJOP breakdown (Bumi & Bangunan), and calculation summary (Tariff, NJOPTKP, PBB Terhutang).

#### [MODIFY] [OfficialDocumentService.php](file:///Users/pondokit/Herd/retribusi-api/app/Services/OfficialDocumentService.php)
- Add `generateSPPT(Bill $bill)` method.
- Logic to extract PBB metadata (luas_tanah, kelas_bumi, luas_bangunan, kelas_bangunan) from `Bill` or `TaxObject` metadata.
- Lookup NJOP values from `PbbNjopClassification` for the breakdown.

#### [MODIFY] [BillController.php](file:///Users/pondokit/Herd/retribusi-api/app/Http/Controllers/BillController.php)
- Add `exportSPPT(Bill $bill)` method.
- Use `OfficialDocumentService` to get data and render the SPPT view.

#### [MODIFY] [api.php](file:///Users/pondokit/Herd/retribusi-api/routes/api.php)
- Add route `GET /bills/{bill}/sppt` in protected group.

#### [DONE] [TaxHierarchySyncSeeder.php](file:///Users/pondokit/Herd/retribusi-api/database/seeders/TaxHierarchySyncSeeder.php)
- Add sync logic for `PBJT - Jasa Catering` (PBJT-CAT) and `PBJT - Jasa Event/Lainnya` (PBJT-EVT) under Wilayah II.

### Proposed Changes

### [Backend] retribusi-api

#### [NEW] [2026_02_19_060000_add_metadata_to_users_table.php](file:///Users/pondokit/Herd/retribusi-api/database/migrations/2026_02_19_060000_add_metadata_to_users_table.php)
- Add `metadata` JSON column to `users` table to support avatar storage.

#### [MODIFY] [User.php](file:///Users/pondokit/Herd/retribusi-api/app/Models/User.php)
- Add `metadata` to `$fillable` and cast it as `array`.

#### [MODIFY] [MeController.php](file:///Users/pondokit/Herd/retribusi-api/app/Http/Controllers/MeController.php)
- Update `update` method to conditionally load relationships based on user model type (`Taxpayer` vs `User`).

---

### [Frontend] retribusi-mobile

#### [MODIFY] [UserProfile.tsx](file:///Users/pondokit/Herd/retribusi-mobile/src/pages/UserProfile.tsx)
- Remove manual `Content-Type: multipart/form-data` from `api.post` call to allow browser to correctly set the boundary.

---

### [Frontend] retribusi-admin

#### [MODIFY] [Profile.tsx](file:///Users/pondokit/Herd/retribusi-admin/src/pages/Profile.tsx)
- Add avatar upload UI and logic to align with the Mobile application.

## Verification Plan

### Automated Tests
- Access `GET /api/bills/{id}/sppt` for a PBB bill and verify PDF generation or data structure.
- Run `php artisan migrate` to ensure column is added.
- Test `POST /api/me/update` with and without avatar.

### Manual Verification
- Check the Petugas/Admin application for a new "Download SPPT" button (if frontend changes are requested later, currently focusing on backend/service).
- Test profile picture update in `retribusi-mobile`.
- Test profile picture update in `retribusi-admin`.

### [retribusi-petugas]
#### [DONE] [ThermalPrintService.ts](file:///Users/pondokit/Herd/retribusi-petugas/src/services/ThermalPrintService.ts)
- Implement **Web Bluetooth API** logic for connecting and sending data to 58mm thermal printers.
