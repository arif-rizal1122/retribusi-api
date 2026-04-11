<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=retribusi;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $districts = [
        'Betoambari',
        'Murhum',
        'Batupoaro',
        'Wolio',
        'Kokalukuna',
        'Bungi',
        'Lea-Lea',
        'Sorawolio',
        'Luar Daerah'
    ];

    $pbjtTypes = [];
    $stmt = $pdo->query("SELECT id, name FROM retribution_types WHERE name LIKE 'PBJT - %'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pbjtTypes[] = $row;
    }

    echo "Creating classifications for " . count($pbjtTypes) . " tax types...\n";

    $now = date('Y-m-d H:i:s');
    $createdCount = 0;

    foreach ($pbjtTypes as $type) {
        $typeName = str_replace('PBJT - ', '', $type['name']);
        
        foreach ($districts as $district) {
            $className = "Kec. $district";
            if ($district == 'Luar Daerah') $className = "Luar Daerah";

            // Check if already exists
            $check = $pdo->prepare("SELECT id FROM retribution_classifications WHERE retribution_type_id = ? AND name = ?");
            $check->execute([$type['id'], $className]);
            
            if ($check->fetch()) {
                continue;
            }

            $ins = $pdo->prepare("
                INSERT INTO retribution_classifications (
                    opd_id, retribution_type_id, name, description, code, 
                    form_schema, requirements, calculation_formula, 
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $fields = json_encode([
                ['key' => 'omzet', 'type' => 'number', 'label' => 'Total Omzet/Nilai', 'required' => true]
            ]);
            $docs = json_encode([
                ['key' => 'foto_lokasi_open_kamera', 'label' => 'Dokumentasi Open Kamera', 'required' => true],
                ['key' => 'formulir_data_dukung', 'label' => 'Upload Formulir Data Dukung', 'required' => true]
            ]);
            $formula = "omzet * 0.10"; // Default 10% for PBJT

            $ins->execute([
                5, // Bapenda
                $type['id'],
                $className,
                "PBJT $typeName - Wilayah $district",
                strtoupper($district),
                $fields,
                $docs,
                $formula,
                $now,
                $now
            ]);
            $createdCount++;
        }
    }

    echo "Successfully created $createdCount classifications.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
