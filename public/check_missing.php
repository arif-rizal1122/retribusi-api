<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    
    // Legacy taxpayers missing objects
    $stmt = $pdoModern->query("
        SELECT tp.id, tp.name, tp.npwpd, tp.object_name, tp.opd_id 
        FROM taxpayers tp 
        LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id 
        WHERE to2.id IS NULL 
          AND tp.npwpd LIKE 'P%' 
          AND (tp.object_name IS NULL OR tp.object_name = '')
    ");
    $legacyMissing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $profileTables = [
        'HOTEL', 'RESTORAN', 'HIBURAN', 'REKLAME', 'JALAN', 
        'MINERAL', 'PARKIR', 'AIRBAWAHTANAH', 'WALET'
    ];

    $results = [];

    foreach ($legacyMissing as $missingTp) {
        $npwpd = $missingTp['npwpd'];
        $foundInLegacy = [];

        foreach ($profileTables as $suffix) {
            $legacyTable = "PATDA_" . $suffix . "_PROFIL";
            try {
                $stmt2 = $pdoLegacy->prepare("SELECT CPM_ID, CPM_NAMA_OP FROM $legacyTable WHERE CPM_NPWPD = ?");
                $stmt2->execute([$npwpd]);
                $objects = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                if (count($objects) > 0) {
                    $foundInLegacy[$legacyTable] = $objects;
                }
            } catch(Exception $e) {}
        }

        if (!empty($foundInLegacy)) {
            $results[] = [
                'modern_id' => $missingTp['id'],
                'name' => $missingTp['name'],
                'npwpd' => $npwpd,
                'found_in_legacy_objects' => $foundInLegacy
            ];
        }
    }

    $output = [
        'Total Missing but actually have Legacy Objects' => count($results),
        'Details' => $results
    ];

    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
