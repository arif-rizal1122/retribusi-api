<?php

set_time_limit(0);

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdoModern->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = [
        'HOTEL' => 'HOTEL',
        'RESTORAN' => 'RESTORAN',
        'HIBURAN' => 'HIBURAN',
        'REKLAME' => 'REKLAME',
        'MINERAL' => 'MINERAL',
        'PARKIR' => 'PARKIR',
        'AIRBAWAHTANAH' => 'AIRBAWAHTANAH',
        'WALET' => 'WALET'
    ];

    echo "Fetching latest tax rates from legacy system...\n";
    $rates = []; // [legacy_id] => rate

    foreach ($tables as $suffix => $displayName) {
        $docTable = "PATDA_" . $suffix . "_DOC";
        echo "Processing $docTable...\n";
        
        // Fetch latest rate for each profil_id
        // We take the max version or latest date
        $stmt = $pdoLegacy->query("
            SELECT CPM_ID_PROFIL, CPM_TARIF_PAJAK 
            FROM $docTable 
            ORDER BY CPM_ID DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['CPM_ID_PROFIL'];
            if (!isset($rates[$id])) {
                $rates[$id] = (float)$row['CPM_TARIF_PAJAK'];
            }
        }
    }

    echo "Found rates for " . count($rates) . " unit businesses.\n";

    echo "Updating M-PAD tax objects metadata...\n";
    $modernStmt = $pdoModern->query("SELECT id, metadata FROM tax_objects WHERE JSON_EXTRACT(metadata, '$.legacy_id') IS NOT NULL");
    $updateStmt = $pdoModern->prepare("UPDATE tax_objects SET metadata = ? WHERE id = ?");

    $count = 0;
    while ($row = $modernStmt->fetch(PDO::FETCH_ASSOC)) {
        $meta = json_decode($row['metadata'], true);
        $legacyId = $meta['legacy_id'];

        if (isset($rates[$legacyId])) {
            $meta['tarif_pajak'] = $rates[$legacyId];
            $updateStmt->execute([json_encode($meta), $row['id']]);
            $count++;
        }
    }

    echo "Successfully enriched $count tax objects with their legacy tax rates.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
