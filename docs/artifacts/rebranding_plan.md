# Rebranding Sipanda to MITRA

Rebranding all system components from "Sipanda" to "MITRA (Mitra Bapenda - Manajemen Integrasi Tax, Retribusi, dan Aset Daerah)".

## Proposed Changes

### [Backend] retribusi-api

#### [MODIFY] [README.md](file:///Users/pondokit/Herd/retribusi-api/docs/README.md)
#### [MODIFY] [core_apis.md](file:///Users/pondokit/Herd/retribusi-api/docs/core_apis.md)
#### [MODIFY] [public_apis.md](file:///Users/pondokit/Herd/retribusi-api/docs/public_apis.md)
#### [MODIFY] [surveillance_tte.md](file:///Users/pondokit/Herd/retribusi-api/docs/surveillance_tte.md)
- Replace all "Sipanda" with "MITRA".

#### [MODIFY] [blade templates](file:///Users/pondokit/Herd/retribusi-api/resources/views/)
- Update `pdf/sspd.blade.php`, `verification/bill.blade.php`, etc.

### [Frontend] retribusi-admin, retribusi-mobile, retribusi-petugas

#### [MODIFY] [index.html](file:///Users/pondokit/Herd/retribusi-admin/index.html)
- Update `<title>` to "MITRA - Mitra Bapenda".

#### [MODIFY] [Login/Landing Pages](file:///Users/pondokit/Herd/retribusi-admin/src/pages/)
- Update UI text and branding.

## Verification Plan

### Manual Verification
- Check UI titles in all apps.
- Verify PDF exports for correct branding.
- Review documentation for consistency.
