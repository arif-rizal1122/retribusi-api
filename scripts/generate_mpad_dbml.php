<?php

$host = '127.0.0.1';
$db   = 'retribusi';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->code);
}

$dbml = "// M-PAD (Retribusi) DBML Schema\n";
$dbml .= "// Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Fetch Tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$exclude = [
    'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 
    'migrations', 'password_reset_tokens', 'personal_access_tokens', 'sessions',
    'audit_logs'
];

foreach ($tables as $table) {
    if (in_array($table, $exclude)) continue;
    
    $dbml .= "Table $table {\n";
    
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
    foreach ($columns as $column) {
        $name = $column['Field'];
        $type = $column['Type'];
        $pk = ($column['Key'] === 'PRI') ? ' [pk]' : '';
        $dbml .= "  $name \"$type\"$pk\n";
    }
    $dbml .= "}\n\n";
}

// Fetch Foreign Keys
$fkQuery = "
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM 
        information_schema.KEY_COLUMN_USAGE 
    WHERE 
        TABLE_SCHEMA = '$db' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
";

$fks = $pdo->query($fkQuery)->fetchAll();

$dbml .= "// Relationships\n";
foreach ($fks as $fk) {
    $dbml .= "Ref: {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} > {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
}

file_put_contents('docs/mpad_schema.dbml', $dbml);
echo "DBML generated successfully at docs/mpad_schema.dbml\n";
