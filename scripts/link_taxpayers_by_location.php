<?php

function normalizeDistrict($name) {
    if (!$name) return "Luar Daerah";
    $n = strtolower(trim($name));
    
    if (strpos($n, 'betoambari') !== false || strpos($n, 'batoambari') !== false || strpos($n, 'betuambari') !== false || strpos($n, 'beroambari') !== false) 
        return "Betoambari";
    if (strpos($n, 'murhum') !== false || strpos($n, 'muhhum') !== false) 
        return "Murhum";
    if (strpos($n, 'batupoaro') !== false || strpos($n, 'batupuaro') !== false || strpos($n, 'betupoaro') !== false || strpos($n, 'batuporo') !== false || strpos($n, 'batu puaro') !== false) 
        return "Batupoaro";
    if (strpos($n, 'wolio') !== false || strpos($n, 'w0li0') !== false || strpos($n, 'walio') !== false) 
        return "Wolio";
    if (strpos($n, 'kokalukuna') !== false) return "Kokalukuna";
    if (strpos($n, 'bungi') !== false) return "Bungi";
    if (strpos($n, 'lea-lea') !== false || strpos($n, 'lealea') !== false) return "Lea-Lea";
    if (strpos($n, 'sorawolio') !== false) return "Sorawolio";
    
    return "Luar Daerah";
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdoModern->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Build Classification Map
    echo "Building Classification Map...\n";
    $classMap = []; // [typeId][normalizedDistrict] = classId
    $stmt = $pdoModern->query("SELECT id, retribution_type_id, name FROM retribution_classifications");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dist = str_replace('Kec. ', '', $row['name']);
        $classMap[$row['retribution_type_id']][$dist] = $row['id'];
    }

    // 2. Fetch Legacy WP Locations
    echo "Fetching legacy locations...\n";
    $legacyLocs = [];
    $stmt = $pdoLegacy->query("SELECT CPM_NPWPD, CPM_KECAMATAN_WP FROM PATDA_WP");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $legacyLocs[strtoupper(trim($row['CPM_NPWPD']))] = normalizeDistrict($row['CPM_KECAMATAN_WP']);
    }

    // 3. Update Modern Links
    echo "Updating taxpayer links...\n";
    $stmt = $pdoModern->query("SELECT trt.id, t.npwpd, trt.retribution_type_id 
                               FROM taxpayer_retribution_type trt 
                               JOIN taxpayers t ON trt.taxpayer_id = t.id");
    
    $updateStmt = $pdoModern->prepare("UPDATE taxpayer_retribution_type SET retribution_classification_id = ? WHERE id = ?");
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $npwpd = strtoupper(trim($row['npwpd']));
        $district = isset($legacyLocs[$npwpd]) ? $legacyLocs[$npwpd] : "Luar Daerah";
        $typeId = $row['retribution_type_id'];

        $classId = isset($classMap[$typeId][$district]) ? $classMap[$typeId][$district] : null;

        if ($classId) {
            $updateStmt->execute([$classId, $row['id']]);
            $count++;
        }
    }

    echo "Successfully linked $count taxpayer records to location-based classifications.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
