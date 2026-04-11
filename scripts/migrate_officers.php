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

function normalizeID($id) {
    if (!$id) return "";
    $clean = preg_replace('/\D/', '', $id);
    // Only return if it looks like a real NIP/NIK (usually 12+ chars, but we use > 5 for safety)
    return (strlen($clean) > 5) ? $clean : "";
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    $legacyData = $pdoLegacy->query("SELECT CPM_USER, CPM_NAMA, CPM_NIP FROM PATDA_PETUGAS")->fetchAll(PDO::FETCH_ASSOC);
    $modernData = $pdoModern->query("SELECT id, name, nik, email FROM users")->fetchAll(PDO::FETCH_ASSOC);

    $defaultPassword = password_hash('Petugas123!', PASSWORD_BCRYPT);
    $defaultOpdId = 5; // Bapenda
    $defaultRole = 'petugas';
    $now = date('Y-m-d H:i:s');

    $addedCount = 0;
    $errors = [];

    foreach ($legacyData as $legacy) {
        $lName = normalizeName($legacy['CPM_NAMA']);
        $lID = normalizeID($legacy['CPM_NIP']);
        $lUser = strtolower(trim($legacy['CPM_USER']));
        $lEmail = $lUser . "@mpad.online";

        // Check if already exists in modern
        $exists = false;
        foreach ($modernData as $modern) {
            $mName = normalizeName($modern['name']);
            $mID = normalizeID($modern['nik']);
            $mEmail = strtolower($modern['email']);

            if (($lID != "" && $lID === $mID) || ($lName != "" && $lName === $mName) || ($lEmail === $mEmail)) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            try {
                $stmt = $pdoModern->prepare("INSERT INTO users (name, email, nik, role, opd_id, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $nik = ($lID != "") ? $lID : null;
                $stmt->execute([
                    $legacy['CPM_NAMA'],
                    $lEmail,
                    $nik,
                    $defaultRole,
                    $defaultOpdId,
                    $defaultPassword,
                    'active',
                    $now,
                    $now
                ]);
                $addedCount++;
            } catch (PDOException $e) {
                $errors[] = "Failed to add {$legacy['CPM_USER']}: " . $e->getMessage();
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'added_count' => $addedCount,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
