<?php
/**
 * fix_orphaned_taxpayers.php
 * Automated scanner for Recommendation #2
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'retribusi';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Identify "Orphaned" Taxpayers (No objects AND no object name)
    $query = "
        SELECT tp.id, tp.name, tp.npwpd, tp.opd_id 
        FROM taxpayers tp 
        LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id 
        WHERE to2.id IS NULL 
        AND (tp.object_name IS NULL OR tp.object_name = '')
    ";
    
    $stmt = $pdo->query($query);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [
        'timestamp' => date('Y-m-d H:i:s'),
        'orphans_count' => count($orphans),
        'orphans_list' => [],
        'analysis' => [
            'legacy_count' => 0,
            'null_opd_count' => 0
        ]
    ];

    foreach ($orphans as $orphan) {
        $isLegacy = (strpos($orphan['npwpd'], 'P') === 0);
        if ($isLegacy) $results['analysis']['legacy_count']++;
        if ($orphan['opd_id'] === null) $results['analysis']['null_opd_count']++;

        $results['orphans_list'][] = [
            'id' => $orphan['id'],
            'name' => $orphan['name'],
            'npwpd' => $orphan['npwpd'],
            'opd_id' => $orphan['opd_id'],
            'is_legacy' => $isLegacy,
            'recommendation' => 'Manual Bind via Admin Dashboard'
        ];
    }

    // 2. Scan for "Mismatched" Taxpayers (Has object_name but 0 tax_objects)
    // These are often victims of sync failures
    $query_mismatch = "
        SELECT tp.id, tp.name, tp.object_name, tp.opd_id 
        FROM taxpayers tp 
        LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id 
        WHERE to2.id IS NULL 
        AND tp.object_name IS NOT NULL AND tp.object_name != ''
    ";
    $stmt_mismatch = $pdo->query($query_mismatch);
    $mismatches = $stmt_mismatch->fetchAll(PDO::FETCH_ASSOC);
    $results['mismatches_count'] = count($mismatches);
    $results['mismatches_list'] = $mismatches;

    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
