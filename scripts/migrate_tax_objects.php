<?php

function normalizeID($id) {
    if (!$id) return "";
    return strtoupper(trim(preg_replace('/\s+/', '', $id)));
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    // 1. Build Mappings (Normalized)
    $taxpayerMap = [];
    $tpStmt = $pdoModern->query("SELECT id, npwpd FROM taxpayers WHERE npwpd IS NOT NULL");
    while ($row = $tpStmt->fetch(PDO::FETCH_ASSOC)) {
        $norm = normalizeID($row['npwpd']);
        if ($norm) $taxpayerMap[$norm] = $row['id'];
    }

    $typeMap = [];
    $rtStmt = $pdoModern->query("SELECT id, name FROM retribution_types WHERE name LIKE 'PBJT - %'");
    while ($row = $rtStmt->fetch(PDO::FETCH_ASSOC)) {
        $cleanName = str_replace('PBJT - ', '', $row['name']);
        $typeMap[strtolower(trim($cleanName))] = $row['id'];
    }

    $profileTables = [
        'HOTEL' => 'Hotel',
        'RESTORAN' => 'Restoran',
        'HIBURAN' => 'Hiburan',
        'REKLAME' => 'Reklame',
        'JALAN' => 'Penerangan Jalan',
        'MINERAL' => 'Mineral Non Logam dan Batuan',
        'PARKIR' => 'Parkir',
        'AIRBAWAHTANAH' => 'Air Bawah Tanah',
        'WALET' => 'Sarang Burung Walet'
    ];

    $results = [];
    $now = date('Y-m-d H:i:s');

    foreach ($profileTables as $suffix => $displayName) {
        $legacyTable = "PATDA_" . $suffix . "_PROFIL";
        $typeId = isset($typeMap[strtolower(trim($displayName))]) ? $typeMap[strtolower(trim($displayName))] : null;

        if (!$typeId) {
            $results[$suffix] = "Error: Type ID not found for $displayName";
            continue;
        }

        $stmt = $pdoLegacy->query("SELECT * FROM $legacyTable");
        $count = 0;
        $errors = 0;
        $missingWP = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $npwpd = normalizeID($row['CPM_NPWPD']);
            $taxpayerId = isset($taxpayerMap[$npwpd]) ? $taxpayerMap[$npwpd] : null;

            if (!$taxpayerId) {
                $missingWP++;
                continue;
            }

            try {
                // Check if this object already exists by nop (CPM_ID)
                $check = $pdoModern->prepare("SELECT id FROM tax_objects WHERE nop = ?");
                $check->execute([$row['CPM_ID']]);
                if ($check->fetch()) {
                    continue; // Skip if already exists
                }

                $ins = $pdoModern->prepare("
                    INSERT INTO tax_objects (
                        nop, taxpayer_id, retribution_type_id, opd_id, name, address, 
                        status, metadata, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $meta = json_encode([
                    'legacy_id' => $row['CPM_ID'], 
                    'legacy_table' => $legacyTable,
                    'district' => $row['CPM_KECAMATAN_OP'] ?? null,
                    'sub_district' => $row['CPM_KELURAHAN_OP'] ?? null
                ]);
                
                $ins->execute([
                    $row['CPM_ID'], // nop
                    $taxpayerId,
                    $typeId,
                    5, // Bapenda
                    $row['CPM_NAMA_OP'] ?? 'Tanpa Nama',
                    $row['CPM_ALAMAT_OP'] ?? null,
                    'active',
                    $meta,
                    $now,
                    $now
                ]);
                $count++;
            } catch (PDOException $e) {
                $errors++;
            }
        }
        $results[$suffix] = "Success: $count, Missing WP: $missingWP, Errors: $errors";
    }

    echo json_encode(['status' => 'success', 'details' => $results], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
