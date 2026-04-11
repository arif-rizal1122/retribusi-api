<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Identify PBJT Type IDs
    echo "Fetching PBJT retribution types...\n";
    $typeIds = [];
    $stmt = $pdo->query("SELECT id FROM retribution_types WHERE name LIKE 'PBJT - %'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $typeIds[] = $row['id'];
    }
    echo "Found " . count($typeIds) . " PBJT types.\n";

    // 2. Identify Migrated Taxpayers
    // WPs created by migrated officers or assigned to OPD 5
    echo "Fetching migrated taxpayers...\n";
    $taxpayerIds = [];
    // We target those created by migrated users (@mpad.online) or newly created 
    $stmt = $pdo->query("SELECT id FROM taxpayers WHERE opd_id = 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $taxpayerIds[] = $row['id'];
    }
    echo "Found " . count($taxpayerIds) . " taxpayers to link.\n";

    // 3. Perform Linking (Pivot Table)
    echo "Linking taxpayers to retribution types...\n";
    $insertedCount = 0;
    
    foreach ($taxpayerIds as $tpId) {
        foreach ($typeIds as $typeId) {
            // Check if link already exists
            $check = $pdo->prepare("SELECT id FROM taxpayer_retribution_type WHERE taxpayer_id = ? AND retribution_type_id = ?");
            $check->execute([$tpId, $typeId]);
            if ($check->fetch()) {
                continue; 
            }

            // Insert into pivot table
            // retribution_classification_id is nullable
            $ins = $pdo->prepare("INSERT INTO taxpayer_retribution_type (taxpayer_id, retribution_type_id) VALUES (?, ?)");
            $ins->execute([$tpId, $typeId]);
            $insertedCount++;
        }
    }

    echo "Successfully created $insertedCount links in taxpayer_retribution_type.\n";
    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
