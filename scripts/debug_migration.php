<?php

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

    $tpStmt = $pdoModern->query("SELECT id, npwpd FROM taxpayers WHERE npwpd IS NOT NULL LIMIT 10");
    echo "Modern Sample:\n";
    while ($row = $tpStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, NPWPD: '{$row['npwpd']}', Normalized: '" . normalizeID($row['npwpd']) . "'\n";
    }

    $lpStmt = $pdoLegacy->query("SELECT CPM_NPWPD FROM PATDA_HOTEL_PROFIL LIMIT 10");
    echo "\nLegacy Sample:\n";
    while ($row = $lpStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "NPWPD: '{$row['CPM_NPWPD']}', Normalized: '" . normalizeID($row['CPM_NPWPD']) . "'\n";
    }

} catch (PDOException $e) {
    echo $e->getMessage();
}
