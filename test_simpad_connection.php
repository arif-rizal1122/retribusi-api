<?php

use App\Services\SimpadKoneksiService;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new SimpadKoneksiService();

echo "Testing legacy connection (mysql_legacy)...\n";

try {
    // Test raw connection first
    $dbName = DB::connection('mysql_legacy')->getDatabaseName();
    echo "Connected to database: $dbName\n";

    // Test querying a table with data
    $count = DB::connection('mysql_legacy')->table('PATDA_HOTEL_DOC')->count();
    echo "Found $count records in PATDA_HOTEL_DOC.\n";

    // Test service method
    $wp = DB::connection('mysql_legacy')->table('PATDA_WP')->first();
    if ($wp) {
        $mapped = $service->mapTaxpayer($wp);
        echo "Successfully mapped first taxpayer: " . ($mapped['name'] ?? 'N/A') . "\n";
    } else {
        echo "No data found in PATDA_WP.\n";
    }

} catch (\Exception $e) {
    echo "Error connecting to legacy database: " . $e->getMessage() . "\n";
    echo "Please check your .env configuration for DB_HOST_LEGACY and DB_DATABASE_LEGACY.\n";
}
