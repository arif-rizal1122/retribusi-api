<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$filename = 'retribusi_local.sql';

$dump = "-- Database: retribusi\n";
$dump .= "-- Generated at: " . now()->format('Y-m-d H:i:s') . "\n\n";
$dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = DB::select('SHOW TABLES');

foreach ($tables as $tableInfo) {
    $table = array_values((array)$tableInfo)[0];
    
    if ($table == 'migrations') continue;

    echo "Exporting table: {$table}\n";
    
    $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $createTableQuery = DB::select("SHOW CREATE TABLE `{$table}`")[0];
    $dump .=  $createTableQuery->{'Create Table'} . ";\n\n";
    
    $rows = DB::table($table)->get();
    
    if ($rows->count() > 0) {
        $dump .= "INSERT INTO `{$table}` VALUES\n";
        
        $values = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ((array)$row as $value) {
                if (is_null($value)) {
                    $rowValues[] = 'NULL';
                } elseif (is_string($value)) {
                    $rowValues[] = "'" . addslashes($value) . "'";
                } else {
                    $rowValues[] = "'" . $value . "'";
                }
            }
            $values[] = "(" . implode(',', $rowValues) . ")";
        }
        
        $dump .= implode(",\n", $values) . ";\n\n";
    }
}

$dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents(base_path($filename), $dump);
echo "Dump saved to {$filename}\n";

