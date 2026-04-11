<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    // 1. Fetch Legacy Types
    $legacyTypes = $pdoLegacy->query("SELECT * FROM PATDA_JENIS_PAJAK WHERE CPM_TIPE <= 12")->fetchAll(PDO::FETCH_ASSOC);

    $now = date('Y-m-d H:i:s');
    $results = [];

    foreach ($legacyTypes as $type) {
        $typeName = "PBJT - " . $type['CPM_JENIS'];
        
        // Check if already exists in modern by name
        $stmt = $pdoModern->prepare("SELECT id FROM retribution_types WHERE name = ?");
        $stmt->execute([$typeName]);
        $existing = $stmt->fetch();

        if ($existing) {
            $results[] = "Existing: $typeName (ID: {$existing['id']})";
        } else {
            $stmt = $pdoModern->prepare("INSERT INTO retribution_types (opd_id, name, tariff_percent, billing_cycle, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([5, $typeName, 10.00, 'monthly', $now, $now]);
            $newId = $pdoModern->lastInsertId();
            $results[] = "Created: $typeName (ID: $newId)";
        }
    }

    echo json_encode(['status' => 'success', 'results' => $results], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
