#!/bin/bash
echo "=========================================="
echo "🚀 STARTING OMNI WORKSPACE TESTING SUITE 🚀"
echo "=========================================="
cd /Users/pondokit/Herd/retribusi-api

echo -e "\n[1/5] PROTOKOL 1: Document Integrity (stg5_document_integrity.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/stg5_document_integrity.php

echo -e "\n[2/5] PROTOKOL 2: RBAC & Isolasi Peran (run_rbac_test.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/run_rbac_test.php

echo -e "\n[3/5] PROTOKOL 3: The Golden Path E2E (run_role_e2e_test.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/run_role_e2e_test.php

echo -e "\n[4/5] Agentic Pentest - Strix (strix_agentic_pentest.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/strix_agentic_pentest.php --url=http://retribusi-api.test

echo -e "\n[5/6] V-Tax Parity Test (test_vtax_parity.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/test_vtax_parity.php

echo -e "\n[6/6] Petugas Workflow Integration Test (run_petugas_workflow_test.php)..."
/Users/pondokit/Library/Application\ Support/Herd/bin/php85 testing/run_petugas_workflow_test.php

echo -e "\n=========================================="
echo "✅ ALL OMNI WORKSPACE TESTS COMPLETED ✅"
echo "=========================================="
