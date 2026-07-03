<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $request = Illuminate\Http\Request::create('/api/me', 'GET');
    // Using a known citizen nik or taxpayer ID
    $user = App\Models\Taxpayer::first();
    if($user) {
        $request->setUserResolver(function() use ($user) { return $user; });
    }
    
    $response = $kernel->handle($request);
    
    echo "STATUS: " . $response->getStatusCode() . "\n";
    echo "CONTENT: " . $response->getContent() . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
