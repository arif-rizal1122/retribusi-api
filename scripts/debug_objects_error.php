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
    $pdoModern->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $taxpayerMap = [];
    $tpStmt = $pdoModern->query("SELECT id, npwpd FROM taxpayers");
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
        // 'RESTORAN' => 'Restoran', // Skip for now to debug faster
    ];

    $results = [];
    $now = date('Y-m-d H:i:s');

    foreach ($profileTables as $suffix => $displayName) {
        $legacyTable = "PATDA_" . $suffix . "_PROFIL";
        $typeId = isset($typeMap[strtolower(trim($displayName))]) ? $typeMap[strtolower(trim($displayName))] : null;

        $stmt = $pdoLegacy->query("SELECT * FROM $legacyTable LIMIT 10");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $npwpd = normalizeID($row['CPM_NPWPD']);
            $taxpayerId = isset($taxpayerMap[$npwpd]) ? $taxpayerMap[$npwpd] : null;

            if (!$taxpayerId) {
                echo "Missing WP: $npwpd\n";
                continue;
            }

            try {
                $ins = $pdoModern->prepare("
                    INSERT INTO tax_objects (
                        taxpayer_id, retribution_type_id, opd_id, name, address, 
                        district, sub_district, status, metadata, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $meta = json_encode(['legacy_id' => $row['CPM_ID'], 'legacy_table' => $legacyTable]);
                
                $ins->execute([
                    $taxpayerId,
                    $typeId,
                    5, // Bapenda
                    $row['CPM_NAMA_OP'] ?? 'Tanpa Nama',
                    $row['CPM_ALAMAT_OP'] ?? null,
                    $row['CPM_KECAMATAN_OP'] ?? null,
                    $row['CPM_KELURAHAN_OP'] ?? null,
                    'active',
                    $meta,
                    $now,
                    $now
                ]);
                echo "Success: " . $row['CPM_NAMA_OP'] . "\n";
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }

} catch (PDOException $e) {
    echo $e->getMessage();
}
