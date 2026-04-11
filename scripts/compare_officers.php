<?php

function normalizeName($name) {
    if (!$name) return "";
    $name = strtolower($name);
    // Remove common titles
    $titles = ['se', 'h.', 'hj', 'sh', 'st', 'msi', 'm.si', 'ak', 'm.ak', 'drs', 'drs.', 'dra', 'dra.', 's.sos', 'sos', 'ssi', 's.si', 'sip', 's.ip', 'si', 's.i', 'md', 'm.d'];
    foreach ($titles as $title) {
        $name = preg_replace('/\b' . preg_quote($title, '/') . '\b/i', '', $name);
    }
    // Remove punctuation and extra spaces
    $name = preg_replace('/[.,]/', '', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return $name;
}

function normalizeID($id) {
    if (!$id) return "";
    // Remove non-numeric (to handle spaces in NIP)
    return preg_replace('/\D/', '', $id);
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);

    $legacyData = $pdoLegacy->query("SELECT CPM_USER, CPM_NAMA, CPM_NIP, CPM_ROLE FROM PATDA_PETUGAS")->fetchAll(PDO::FETCH_ASSOC);
    $modernData = $pdoModern->query("SELECT id, name, email, nik, role FROM users WHERE role != 'citizen'")->fetchAll(PDO::FETCH_ASSOC);

    $report = [
        'matches' => [],
        'missing_in_modern' => [],
        'extra_in_modern' => []
    ];

    $modernMatchedIds = [];

    foreach ($legacyData as $legacy) {
        $matched = false;
        $lName = normalizeName($legacy['CPM_NAMA']);
        $lID = normalizeID($legacy['CPM_NIP']);

        foreach ($modernData as $modern) {
            $mName = normalizeName($modern['name']);
            $mID = normalizeID($modern['nik']);

            // Match by normalized ID (NIP/NIK)
            $idMatch = (!empty($lID) && $lID === $mID);
            // Match by normalized Name
            $nameMatch = (!empty($lName) && $lName === $mName);

            if ($idMatch || $nameMatch) {
                $report['matches'][] = [
                    'legacy' => $legacy,
                    'modern' => $modern,
                    'match_type' => $idMatch ? 'ID' : 'Name'
                ];
                $modernMatchedIds[] = $modern['id'];
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $report['missing_in_modern'][] = $legacy;
        }
    }

    foreach ($modernData as $modern) {
        if (!in_array($modern['id'], $modernMatchedIds)) {
            $report['extra_in_modern'][] = $modern;
        }
    }

    echo json_encode($report, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
