<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    // 1. Build User Mapping (Legacy Username -> Modern User ID)
    // We already migrated 114 users, and some existed before.
    // The email format for migrated users is [user]@mpad.online
    $userMap = [];
    $modernUsers = $pdoModern->query("SELECT id, email, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modernUsers as $u) {
        $username = explode('@', $u['email'])[0];
        $userMap[$username] = $u['id'];
        // Also map by name if possible (fuzzy)
        $userMap[strtolower($u['name'])] = $u['id'];
    }

    // 2. Fetch Legacy WP
    $legacyWP = $pdoLegacy->query("SELECT * FROM PATDA_WP")->fetchAll(PDO::FETCH_ASSOC);

    $insertedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    $now = date('Y-m-d H:i:s');
    $defaultOpdId = 5; // Bapenda

    // Prepare existence check statement
    $checkStmt = $pdoModern->prepare("SELECT id FROM taxpayers WHERE npwpd = ? OR name = ?");

    // Prepare insert statement
    $insertStmt = $pdoModern->prepare("
        INSERT INTO taxpayers (
            opd_id, created_by, nik, name, address, 
            district, sub_district, phone, npwpd, 
            is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($legacyWP as $wp) {
        $npwpd = trim($wp['CPM_NPWPD']);
        $name = trim($wp['CPM_NAMA_WP']);
        
        // Skip empty names (invalid data)
        if (empty($name)) {
            $skippedCount++;
            continue;
        }

        // Check if already exists
        $checkStmt->execute([$npwpd, $name]);
        if ($checkStmt->fetch()) {
            $skippedCount++;
            continue;
        }

        // Determine created_by
        $author = strtolower(trim($wp['CPM_AUTHOR']));
        $createdBy = isset($userMap[$author]) ? $userMap[$author] : null;

        try {
            $insertStmt->execute([
                $defaultOpdId,
                $createdBy,
                null, // nik
                $name,
                $wp['CPM_ALAMAT_WP'],
                $wp['CPM_KECAMATAN_WP'],
                $wp['CPM_KELURAHAN_WP'],
                $wp['CPM_TELEPON_WP'],
                $npwpd,
                1, // is_active
                $now,
                $now
            ]);
            $insertedCount++;
        } catch (PDOException $e) {
            $errorCount++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'total_legacy' => count($legacyWP),
        'inserted' => $insertedCount,
        'skipped' => $skippedCount,
        'errors' => $errorCount
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
