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
    $stmt = $pdo->query("SELECT id, name FROM retribution_types WHERE name LIKE 'PBJT - %'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $typeIds[] = $row['id'];
        echo "- Found: {$row['name']} (ID: {$row['id']})\n";
    }

    if (empty($typeIds)) {
        die("Error: No PBJT types found. Run migrate_tax_types.php first.\n");
    }

    // 2. Identify Migrated Officers
    echo "Fetching migrated officers (@mpad.online)...\n";
    $userIds = [];
    $stmt = $pdo->query("SELECT id, name FROM users WHERE email LIKE '%@mpad.online' AND role = 'petugas'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userIds[] = $row['id'];
    }
    echo "Found " . count($userIds) . " officers.\n";

    // 3. Perform Assignments
    $now = date('Y-m-d H:i:s');
    $insertedCount = 0;
    
    foreach ($userIds as $userId) {
        foreach ($typeIds as $typeId) {
            // Check if assignment already exists
            $check = $pdo->prepare("SELECT id FROM user_retribution_assignments WHERE user_id = ? AND retribution_type_id = ?");
            $check->execute([$userId, $typeId]);
            if ($check->fetch()) {
                continue; // Skip if already assigned
            }

            $ins = $pdo->prepare("INSERT INTO user_retribution_assignments (user_id, retribution_type_id, created_at, updated_at) VALUES (?, ?, ?, ?)");
            $ins->execute([$userId, $typeId, $now, $now]);
            $insertedCount++;
        }
    }

    echo "Successfully assigned $insertedCount new retribution mappings to officers.\n";
    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
