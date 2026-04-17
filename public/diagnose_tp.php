<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    
    // Total taxpayers
    $stmt1 = $pdo->query("SELECT count(*) as count FROM taxpayers");
    $total_tp = $stmt1->fetchColumn();

    // Taxpayers with NO objects
    $stmt2 = $pdo->query("SELECT count(*) as count FROM taxpayers tp LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id WHERE to2.id IS NULL");
    $no_objects = $stmt2->fetchColumn();

    // Taxpayers with NO objects and NO object_name string
    $stmt3 = $pdo->query("SELECT count(*) as count FROM taxpayers tp LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id WHERE to2.id IS NULL AND (tp.object_name IS NULL OR tp.object_name = '')");
    $no_objects_and_no_string = $stmt3->fetchColumn();

    // Taxpayers without OPD
    $stmt4 = $pdo->query("SELECT count(*) as count FROM taxpayers WHERE opd_id IS NULL");
    $no_opd = $stmt4->fetchColumn();
    
    // Sample 5 taxpayers without objects
    $stmt5 = $pdo->query("SELECT tp.id, tp.name, tp.nik, tp.npwpd, tp.object_name, tp.opd_id FROM taxpayers tp LEFT JOIN tax_objects to2 ON tp.id = to2.taxpayer_id WHERE to2.id IS NULL AND (tp.object_name IS NULL OR tp.object_name = '') LIMIT 5");
    $samples = $stmt5->fetchAll(PDO::FETCH_ASSOC);

    $output = [
        'Total Taxpayers' => $total_tp,
        'Taxpayers with 0 Tax_Objects' => $no_objects,
        'Taxpayers with 0 Tax_Objects AND empty object_name field' => $no_objects_and_no_string,
        'Taxpayers with null OPD_ID' => $no_opd,
        'Samples of missing objects' => $samples
    ];

    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
