<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
\$app = new \Illuminate\Container\Container();
\Illuminate\Support\Facades\Facade::setFacadeApplication(\$app);
\$app->singleton('http', function() { return new \Illuminate\Http\Client\Factory(); });
\$base_url = 'http://103.182.72.241:8000/pospbb/Api_pos';
\$credentials = [
    ['u' => 'MITRA_BAUBAU', 'p' => 'MitraPassword2025!'],
    ['u' => 'MITRA_TEST_2026', 'p' => 'Testing123!'],
    ['u' => 'USER1', 'p' => 'admin'],
    ['u' => 'BAPENDA_BAUBAU', 'p' => 'Bapenda123#'],
];
foreach (\$credentials as \$cred) {
    echo "Testing: " . \$cred['u'] . " / " . \$cred['p'] . "\n";
    try {
        \$response = \Illuminate\Support\Facades\Http::asForm()->timeout(5)
            ->post(\$base_url . "/login", [
                'USERNAME' => \$cred['u'],
                'PASSWORD' => \$cred['p']
            ]);
        echo "Status: " . \$response->status() . " Body: " . \$response->body() . "\n";
    } catch (\Exception \$e) {
        echo "Error: " . \$e->getMessage() . "\n";
    }
}
