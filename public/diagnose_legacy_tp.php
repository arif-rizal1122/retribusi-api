<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    
    // Legacy taxpayers missing objects
    $stmt = $pdo->query("
        SELECT tp.id, tp.name, tp.npwpd, tp.object_name, tp.opd_id 
        FROM taxpayers tp 
        LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id 
        WHERE to2.id IS NULL 
          AND tp.npwpd LIKE 'P%' 
          AND (tp.object_name IS NULL OR tp.object_name = '')
    ");
    $legacyMissing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = [
        'Total Legacy Taxpayers Missing Objects' => count($legacyMissing),
        'Samples' => array_slice($legacyMissing, 0, 10)
    ];

    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
