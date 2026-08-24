<?php
/**
 * deep_forensic_legacy.php
 * Scan all legacy tables for traces of specific NPWPDs
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db_legacy = 'sw_patda';
$db_modern = 'retribusi';

try {
    $pdo_legacy = new PDO("mysql:host=$host;dbname=$db_legacy;charset=utf8mb4", $user, $pass);
    $pdo_modern = new PDO("mysql:host=$host;dbname=$db_modern;charset=utf8mb4", $user, $pass);

    // 1. Get legacy orphans from modern DB
    $query_orphans = "
        SELECT npwpd, name FROM taxpayers 
        WHERE npwpd LIKE 'P%' 
        AND id NOT IN (SELECT taxpayer_id FROM tax_objects)
    ";
    $orphans = $pdo_modern->query($query_orphans)->fetchAll(PDO::FETCH_ASSOC);
    $npwpds = array_column($orphans, 'npwpd');

    if (empty($npwpds)) {
        echo "No legacy orphans found in modern DB.\n";
        exit;
    }

    // 2. Define profile tables to scan
    $profile_tables = [
        'PATDA_AIRBAWAHTANAH_PROFIL',
        'PATDA_HIBURAN_PROFIL',
        'PATDA_HOTEL_PROFIL',
        'PATDA_JALAN_PROFIL',
        'PATDA_MINERAL_PROFIL',
        'PATDA_PARKIR_PROFIL',
        'PATDA_REKLAME_PROFIL',
        'PATDA_RESTORAN_PROFIL',
        'PATDA_WALET_PROFIL'
    ];

    $findings = [];

    foreach ($npwpds as $npwpd) {
        $findings[$npwpd] = [
            'name' => '',
            'found_in' => []
        ];
        
        // Find name in PATDA_WP
        $stmt_wp = $pdo_legacy->prepare("SELECT CPM_NAMA_WP FROM PATDA_WP WHERE CPM_NPWPD = ?");
        $stmt_wp->execute([$npwpd]);
        $findings[$npwpd]['name'] = $stmt_wp->fetchColumn();

        // Scan profile tables
        foreach ($profile_tables as $table) {
            $stmt = $pdo_legacy->prepare("SELECT count(*) FROM $table WHERE CPM_NPWPD = ?");
            $stmt->execute([$npwpd]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Get more info
                $stmt_info = $pdo_legacy->prepare("SELECT CPM_NAMA_OP, CPM_ALAMAT_OP FROM $table WHERE CPM_NPWPD = ?");
                $stmt_info->execute([$npwpd]);
                $info = $stmt_info->fetchAll(PDO::FETCH_ASSOC);
                
                $findings[$npwpd]['found_in'][] = [
                    'table' => $table,
                    'count' => $count,
                    'details' => $info
                ];
            }
        }

        // Scan Document Main tables (Transactions)
        $doc_tables = [
            'PATDA_AIRBAWAHTANAH_DOC_TRANMAIN',
            'PATDA_HIBURAN_DOC_TRANMAIN',
            'PATDA_HOTEL_DOC_TRANMAIN',
            'PATDA_JALAN_DOC_TRANMAIN',
            'PATDA_MINERAL_DOC_TRANMAIN',
            'PATDA_PARKIR_DOC_TRANMAIN',
            'PATDA_REKLAME_DOC_TRANMAIN',
            'PATDA_RESTORAN_DOC_TRANMAIN',
            'PATDA_WALET_DOC_TRANMAIN'
        ];

        foreach ($doc_tables as $table) {
            // In some tables it might be CPM_NPWPD or similar
            try {
                $stmt = $pdo_legacy->prepare("SELECT count(*) FROM $table WHERE CPM_NPWPD = ?");
                $stmt->execute([$npwpd]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $findings[$npwpd]['found_in'][] = [
                        'table' => $table,
                        'count' => $count,
                        'type' => 'TRANSACTION'
                    ];
                }
            } catch (Exception $e) { /* skip */ }
        }
    }

    echo json_encode($findings, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
