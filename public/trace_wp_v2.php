<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdoLegacy = new PDO("mysql:host=$host;dbname=sw_patda;charset=utf8mb4", $user, $pass);
    $pdoModern = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    
    $npwpd = 'P10001710003';
    
    $output = [
        'legacy' => [],
        'modern' => []
    ];
    
    // Check PATDA_WP
    $stmt = $pdoLegacy->prepare("SELECT CPM_NPWPD, CPM_NAMA_WP FROM PATDA_WP WHERE CPM_NPWPD = ?");
    $stmt->execute([$npwpd]);
    $output['legacy']['PATDA_WP'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $profileTables = [
        'HOTEL', 'RESTORAN', 'HIBURAN', 'REKLAME', 'JALAN', 
        'MINERAL', 'PARKIR', 'AIRBAWAHTANAH', 'WALET'
    ];

    foreach ($profileTables as $suffix) {
        $legacyTable = "PATDA_" . $suffix . "_PROFIL";
        
        try {
            $stmt = $pdoLegacy->prepare("SELECT CPM_ID, CPM_NPWPD, CPM_NAMA_OP, CPM_NAMA_WP FROM $legacyTable WHERE CPM_NPWPD = ?");
            $stmt->execute([$npwpd]);
            $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($objects) > 0) {
                $output['legacy'][$legacyTable] = $objects;
            }
        } catch(Exception $e) {}
    }
    
    // Check modern taxpayers table
    $stmt = $pdoModern->prepare("SELECT id, name, npwpd FROM taxpayers WHERE npwpd = ?");
    $stmt->execute([$npwpd]);
    $taxpayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output['modern']['taxpayers'] = $taxpayers;
    
    if (count($taxpayers) > 0) {
        $taxpayerId = $taxpayers[0]['id'];
        
        // Check objects by taxpayer_id
        $stmt2 = $pdoModern->prepare("SELECT id, nop, taxpayer_id, name, address, retribution_type_id FROM tax_objects WHERE taxpayer_id = ?");
        $stmt2->execute([$taxpayerId]);
        $output['modern']['tax_objects_by_taxpayer_id'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Also let's check if the legacy NOPs exist under a different taxpayer
    $legacy_nops = ['c7a88f5ed20f11ea9d81089e017d22ea', '00d4bea24a9c3d643598508d1f7ca048'];
    $stmt3 = $pdoModern->prepare("SELECT id, nop, taxpayer_id, name FROM tax_objects WHERE nop IN (?, ?)");
    $stmt3->execute($legacy_nops);
    $output['modern']['tax_objects_by_nop'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    // If it exists, let's see to which taxpayer it belongs
    if (count($output['modern']['tax_objects_by_nop']) > 0) {
        $found_tp_id = $output['modern']['tax_objects_by_nop'][0]['taxpayer_id'];
        $stmt4 = $pdoModern->prepare("SELECT id, name, npwpd FROM taxpayers WHERE id = ?");
        $stmt4->execute([$found_tp_id]);
        $output['modern']['taxpayer_for_nop'] = $stmt4->fetch(PDO::FETCH_ASSOC);
    }

    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
