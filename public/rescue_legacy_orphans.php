<?php
/**
 * rescue_legacy_orphans.php
 * Script to automatically link orphaned legacy taxpayers to their modern categories
 * Based on CPM_JENIS_PAJAK in sw_patda.PATDA_WP
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db_legacy = 'sw_patda';
$db_modern = 'retribusi';

try {
    $pdo_legacy = new PDO("mysql:host=$host;dbname=$db_legacy;charset=utf8mb4", $user, $pass);
    $pdo_modern = new PDO("mysql:host=$host;dbname=$db_modern;charset=utf8mb4", $user, $pass);
    $pdo_modern->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mapping Legacy CPM_TIPE to Modern retribution_classification_id
    $mapping = [
        '4' => '193', // Hotel -> PBJT Perhotelan
        '5' => '192', // Restoran -> PBJT Makan Minum
        '6' => '194', // Hiburan -> PBJT Hiburan
        '7' => '188', // Reklame -> Pajak Reklame
        '8' => '196', // Penerangan Jalan -> PBJT Tenaga Listrik
        '9' => '189', // MBLB -> Pajak MBLB
        '10' => '172', // Parkir -> PBJT Parkir
        '11' => '197', // Air Bawah Tanah -> Pajak Air Tanah
        '12' => '190', // Walet -> Pajak Sarang Burung Walet
    ];

    // Default OPD for these taxes (Bapenda)
    $target_opd = 5;

    // 1. Get legacy orphans from modern DB
    $query_orphans = "
        SELECT id, npwpd, name, opd_id FROM taxpayers 
        WHERE npwpd LIKE 'P%' 
        AND id NOT IN (SELECT taxpayer_id FROM tax_objects)
    ";
    $orphans = $pdo_modern->query($query_orphans)->fetchAll(PDO::FETCH_ASSOC);

    $results = [
        'processed' => 0,
        'linked' => 0,
        'failed' => 0,
        'details' => []
    ];

    foreach ($orphans as $orphan) {
        $results['processed']++;
        $npwpd = $orphan['npwpd'];
        
        // Fetch legacy jenis pajak
        $stmt_legacy = $pdo_legacy->prepare("SELECT CPM_JENIS_PAJAK FROM PATDA_WP WHERE CPM_NPWPD = ?");
        $stmt_legacy->execute([$npwpd]);
        $jenis_pajak_str = $stmt_legacy->fetchColumn();

        if (!$jenis_pajak_str) {
            $results['details'][] = "No legacy tax type found for $npwpd ({$orphan['name']})";
            continue;
        }

        // Handle multiple types (e.g. "7;8")
        $types = explode(';', $jenis_pajak_str);
        $linked_count_inner = 0;

        foreach ($types as $legacy_type) {
            $legacy_type = trim($legacy_type);
            if (isset($mapping[$legacy_type])) {
                $modern_id = $mapping[$legacy_type];
                
                // Get the retribution_type_id associated with this classification
                $stmt_type = $pdo_modern->prepare("SELECT retribution_type_id FROM retribution_classifications WHERE id = ?");
                $stmt_type->execute([$modern_id]);
                $retribution_type_id = $stmt_type->fetchColumn();

                if ($retribution_type_id) {
                    try {
                        // Link in taxpayer_retribution_type pivot table
                        // First check if already linked
                        $stmt_check = $pdo_modern->prepare("SELECT count(*) FROM taxpayer_retribution_type WHERE taxpayer_id = ? AND retribution_type_id = ?");
                        $stmt_check->execute([$orphan['id'], $retribution_type_id]);
                        
                        if ($stmt_check->fetchColumn() == 0) {
                            $stmt_insert = $pdo_modern->prepare("INSERT INTO taxpayer_retribution_type (taxpayer_id, retribution_type_id, retribution_classification_id) VALUES (?, ?, ?)");
                            $stmt_insert->execute([$orphan['id'], $retribution_type_id, $modern_id]);
                            
                            // Also update Taxpayer's OPD if it was null or different
                            if ($orphan['opd_id'] != $target_opd) {
                                $pdo_modern->prepare("UPDATE taxpayers SET opd_id = ? WHERE id = ?")->execute([$target_opd, $orphan['id']]);
                            }

                            $linked_count_inner++;
                        }
                    } catch (Exception $e) {
                        $results['failed']++;
                        $results['details'][] = "Error linking $npwpd to type $legacy_type: " . $e->getMessage();
                    }
                }
            }
        }

        if ($linked_count_inner > 0) {
            $results['linked']++;
            $results['details'][] = "Successfully linked $npwpd ({$orphan['name']}) to $linked_count_inner categories.";
        }
    }

    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
