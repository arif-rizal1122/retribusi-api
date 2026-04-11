<?php

set_time_limit(0);
ini_set('memory_limit', '1G');

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdoModern->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Status Mapping Dictionary
    $statusMap = [
        '1' => 'Draft / Pendataan',
        '2' => 'Verifikasi / Penetapan',
        '3' => 'Menunggu Pembayaran',
        '4' => 'Ditolak',
        '5' => 'Lunas / Penagihan (Selesai)'
    ];

    // 1. Build Transaction Map (Legacy ID -> Modern ID)
    echo "Building Transaction Map...\n";
    $transMap = [];
    $stmt = $pdoModern->query("SELECT id, metadata FROM tax_transactions WHERE source = '9pajak'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $meta = json_decode($row['metadata'], true);
        if (isset($meta['legacy_id'])) {
            $transMap[$meta['legacy_id']] = $row['id'];
        }
    }
    echo "Transaction Map Built: " . count($transMap) . " records.\n";

    $mainTables = [
        'HOTEL' => 'HOTEL',
        'RESTORAN' => 'RESTORAN',
        'HIBURAN' => 'HIBURAN',
        'REKLAME' => 'REKLAME'
    ];

    foreach ($mainTables as $suffix => $legacyName) {
        $legacyTable = "PATDA_" . $suffix . "_DOC_TRANMAIN";
        echo "Processing $legacyTable...\n";
        
        $colId = "CPM_TRAN_" . $legacyName . "_ID";
        
        // Fetch all logs for this table
        $stmt = $pdoLegacy->query("SELECT * FROM $legacyTable ORDER BY CPM_TRAN_CLAIM_DATETIME ASC");
        
        $logsByDoc = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $docId = $row[$colId];
            if (!isset($transMap[$docId])) continue;
            
            $statusText = isset($statusMap[$row['CPM_TRAN_STATUS']]) ? $statusMap[$row['CPM_TRAN_STATUS']] : "Status {$row['CPM_TRAN_STATUS']}";
            
            $logsByDoc[$docId][] = [
                'date' => $row['CPM_TRAN_CLAIM_DATETIME'],
                'opr' => $row['CPM_TRAN_OPR'] ?: $row['CPM_TRAN_OPR_DISPENDA'],
                'status' => $statusText,
                'info' => $row['CPM_TRAN_INFO']
            ];
        }

        echo "Updating metadata for " . count($logsByDoc) . " documents...\n";
        
        $updateStmt = $pdoModern->prepare("UPDATE tax_transactions SET metadata = ? WHERE id = ?");
        
        $count = 0;
        foreach ($logsByDoc as $docId => $logs) {
            $modId = $transMap[$docId];
            
            // Fetch current metadata to avoid overwriting legacy_id
            $currentStmt = $pdoModern->prepare("SELECT metadata FROM tax_transactions WHERE id = ?");
            $currentStmt->execute([$modId]);
            $currentMeta = json_decode($currentStmt->fetchColumn(), true);
            
            $currentMeta['history_logs'] = $logs;
            
            $updateStmt->execute([json_encode($currentMeta), $modId]);
            $count++;
            
            if ($count % 500 == 0) echo "Updated $count records...\n";
        }
        echo "Finished $legacyTable. Total updated: $count\n";
    }

    echo json_encode(['status' => 'success'], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
