<?php

set_time_limit(0); 
ini_set('memory_limit', '1G');

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

    // 1. Build Object Map (Legacy ID -> Modern ID & Taxpayer ID)
    echo "Building Object Map...\n";
    $opMap = [];
    $opStmt = $pdoModern->query("SELECT id, taxpayer_id, nop FROM tax_objects WHERE nop IS NOT NULL");
    while ($row = $opStmt->fetch(PDO::FETCH_ASSOC)) {
        $opMap[normalizeID($row['nop'])] = [
            'id' => $row['id'],
            'taxpayer_id' => $row['taxpayer_id']
        ];
    }
    echo "Object Map Built: " . count($opMap) . " records.\n";

    $docTables = [
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

    foreach ($docTables as $suffix => $displayName) {
        $legacyTable = "PATDA_" . $suffix . "_DOC";
        echo "Processing $legacyTable...\n";
        
        $stmt = $pdoLegacy->query("SELECT * FROM $legacyTable");
        
        $batchSize = 1000;
        $batch = [];
        $count = 0;
        $skipped = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $legacyOpId = normalizeID($row['CPM_ID_PROFIL']);
            $mapped = isset($opMap[$legacyOpId]) ? $opMap[$legacyOpId] : null;

            if (!$mapped) {
                $skipped++;
                continue;
            }

            // Prepare values for batch insert
            $batch[] = [
                5, // opd_id
                $mapped['taxpayer_id'],
                $mapped['id'],
                $row['CPM_TGL_LAPOR'] ? date('Y-m-d', strtotime($row['CPM_TGL_LAPOR'])) : date('Y-m-d'),
                (float)$row['CPM_TOTAL_OMZET'],
                (float)$row['CPM_TOTAL_PAJAK'],
                '9pajak',
                "Migrasi Historis $displayName (Legacy ID: {$row['CPM_ID']})",
                json_encode(['legacy_id' => $row['CPM_ID']]),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ];

            if (count($batch) >= $batchSize) {
                insertBatch($pdoModern, $batch);
                $count += count($batch);
                $batch = [];
                echo "Handled $count records...\n";
            }
        }

        // Insert remaining
        if (count($batch) > 0) {
            insertBatch($pdoModern, $batch);
            $count += count($batch);
        }
        
        echo "Finished $legacyTable. Total: $count, Skipped: $skipped\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

function insertBatch($pdo, $data) {
    if (empty($data)) return;
    
    $cols = "opd_id, taxpayer_id, tax_object_id, transaction_date, amount, tax_amount, source, description, metadata, created_at, updated_at";
    $placeholders = str_repeat("(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?), ", count($data) - 1) . "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $sql = "INSERT INTO tax_transactions ($cols) VALUES $placeholders";
    $stmt = $pdo->prepare($sql);
    
    $flatData = [];
    foreach ($data as $row) {
        foreach ($row as $val) {
            $flatData[] = $val;
        }
    }
    
    $stmt->execute($flatData);
}
