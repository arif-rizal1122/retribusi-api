<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all PBJT classifications
    $stmt = $pdo->query("
        SELECT rc.id, rt.name as type_name 
        FROM retribution_classifications rc 
        JOIN retribution_types rt ON rc.retribution_type_id = rt.id 
        WHERE rt.name LIKE 'PBJT - %'
    ");
    
    $classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Updating schemas for " . count($classifications) . " classifications...\n";

    $updateStmt = $pdo->prepare("UPDATE retribution_classifications SET form_schema = ?, calculation_formula = ? WHERE id = ?");

    foreach ($classifications as $class) {
        $typeName = strtolower($class['type_name']);
        
        // Define fields based on type
        $fields = [
            ['key' => 'omzet', 'type' => 'number', 'label' => 'Total Omzet/Nilai', 'required' => true],
            ['key' => 'tarif_pajak', 'type' => 'number', 'label' => 'Tarif Pajak (%)', 'required' => true, 'readonly' => true],
            ['key' => 'keterangan', 'type' => 'text', 'label' => 'Keterangan / Deskripsi', 'required' => false]
        ];

        // Specific formula that uses the tarif_pajak field
        $formula = "omzet * (tarif_pajak / 100)";

        $updateStmt->execute([
            json_encode($fields),
            $formula,
            $class['id']
        ]);
    }

    echo "Successfully updated form schemas and calculation formulas.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
