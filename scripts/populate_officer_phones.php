<?php

function normalizeName($name) {
    if (!$name) return "";
    $name = strtolower($name);
    $titles = ['se', 'h.', 'hj', 'sh', 'st', 'msi', 'm.si', 'ak', 'm.ak', 'drs', 'drs.', 'dra', 'dra.', 's.sos', 'sos', 'ssi', 's.si', 'sip', 's.ip', 'si', 's.i', 'md', 'm.d'];
    foreach ($titles as $title) {
        $name = preg_replace('/\b' . preg_quote($title, '/') . '\b/i', '', $name);
    }
    $name = preg_replace('/[.,]/', '', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return $name;
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    // Get phone numbers from Legacy PATDA_WP (joining with common usernames if possible)
    $legacyPhones = $pdoLegacy->query("
        SELECT CPM_USER, CPM_NAMA_WP, CPM_TELEPON_WP 
        FROM PATDA_WP 
        WHERE CPM_TELEPON_WP IS NOT NULL 
          AND CPM_TELEPON_WP != '' 
          AND CPM_TELEPON_WP != '0'
    ")->fetchAll(PDO::FETCH_ASSOC);

    $updatedCount = 0;

    foreach ($legacyPhones as $row) {
        $lUser = strtolower(trim($row['CPM_USER']));
        $lName = normalizeName($row['CPM_NAMA_WP']);
        $phoneNumber = trim($row['CPM_TELEPON_WP']);

        // Try to update by email ([user]@mpad.online) or name
        $stmt = $pdoModern->prepare("
            UPDATE users 
            SET phone = ? 
            WHERE (email = ?) 
               OR (LOWER(name) = ? AND role != 'citizen')
            AND (phone IS NULL OR phone = '')
        ");
        
        $stmt->execute([
            $phoneNumber,
            $lUser . "@mpad.online",
            strtolower(trim($row['CPM_NAMA_WP']))
        ]);
        
        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }

    echo json_encode(['status' => 'success', 'phones_populated' => $updatedCount], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
