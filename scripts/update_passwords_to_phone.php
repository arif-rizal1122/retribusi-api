<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    // Select all officers/admins who have a phone number
    $stmt = $pdo->query("SELECT id, email, name, phone FROM users WHERE role != 'citizen' AND phone IS NOT NULL AND phone != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updatedCount = 0;
    $skippedCount = 0;
    $noPhoneList = [];

    foreach ($users as $u) {
        // Clean phone number: remove non-numeric
        $normPhone = preg_replace('/\D/', '', $u['phone']);

        if (!empty($normPhone)) {
            $hashedPassword = password_hash($normPhone, PASSWORD_BCRYPT);
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $u['id']]);
            $updatedCount++;
        } else {
            $skippedCount++;
        }
    }

    // Also get list of officers who STILL have no phone number
    $noPhoneStmt = $pdo->query("SELECT name, email FROM users WHERE role != 'citizen' AND (phone IS NULL OR phone = '')");
    $noPhoneUsers = $noPhoneStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'passwords_updated' => $updatedCount,
        'skipped_empty_norm' => $skippedCount,
        'officers_missing_phone' => count($noPhoneUsers),
        'missing_list_sample' => array_slice($noPhoneUsers, 0, 10)
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
