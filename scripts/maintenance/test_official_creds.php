<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
$app = new \Illuminate\Container\Container();
\Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->singleton('http', function() { return new \Illuminate\Http\Client\Factory(); });
$base_url = 'http://103.182.72.241:8000/pospbb/Api_service';
$credentials = [
    ['u' => 'MITRA_BAUBAU', 'p' => '[REDACTED]'],
    ['u' => 'MITRA_TEST_2026', 'p' => '[REDACTED]'],
    ['u' => 'USER1', 'p' => '[REDACTED]'],
    ['u' => 'BAPENDA_BAUBAU', 'p' => '[REDACTED]'],
];
foreach ($credentials as $cred) {
    echo "Testing: " . $cred['u'] . " / [REDACTED]\n";
    // Security Note: Credential testing should use environment variables.
}
