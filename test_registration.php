<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
$app = new \Illuminate\Container\Container();
\Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->singleton('http', function() { return new \Illuminate\Http\Client\Factory(); });

$base_url = 'http://103.182.72.241:8000/pospbb/Api_service';

// Attempt to register 'mpad' with 'mpad2026'
$u = 'mpad';
$p = 'mpad2026';
$o = 'm-PAD';

echo "Testing Registration for $u / $p...\n";
$response = Http::asJson()->post("$base_url/register", [
    'USERNAME' => $u,
    'PASSWORD' => $p,
    'OUTLET' => $o
]);

echo "Status: " . $response->status() . " Body: " . $response->body() . "\n";
