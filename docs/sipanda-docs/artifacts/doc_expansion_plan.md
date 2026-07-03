# Expanding API Documentation

This plan outlines the expansion of API documentation for the Sipanda project, covering existing features and new implementations.

## Proposed Changes

### [Backend] retribusi-api

#### [NEW] [README.md](file:///Users/pondokit/Herd/retribusi-api/docs/README.md)
- Create a main entry point for documentation, categorizing different API modules.

#### [NEW] [public_apis.md](file:///Users/pondokit/Herd/retribusi-api/docs/public_apis.md)
- Document public endpoints: Login (Citizen/User), Tax Simulation (`/simulate-tax`), PBB Lookup (`/pbb/lookup-class`), etc.

#### [NEW] [protected_apis.md](file:///Users/pondokit/Herd/retribusi-api/docs/protected_apis.md)
- Document core application APIs: Taxpayers, Tax Objects, Bills, Payments.

#### [NEW] [surveillance_tte.md](file:///Users/pondokit/Herd/retribusi-api/docs/surveillance_tte.md)
- Document advanced modules: Surveillance (Audit Logs, Enforcement), E-Registry & TTE.

#### [MODIFY] [spopd-form-structure.md](file:///Users/pondokit/Herd/retribusi-api/docs/spopd-form-structure.md)
- Ensure SPPT Online (PBB) data structures are included in the documentation.

## Verification Plan

### Manual Verification
- Review generated markdown files to ensure accuracy against `routes/api.php` and controller logic.
- Verify internal links between documentation files.
