<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Use the token or just act as a specific user if easier
// Looking at the token 173|..., let's see which user it belongs to
$tokenId = 173; 
$token = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
if ($token) {
    $user = $token->tokenable;
    echo "Acting as user: " . $user->email . " (Role: " . $user->role . ")\n";
} else {
    echo "Token not found. Defaulting to super_admin for testing.\n";
    $user = User::where('role', 'super_admin')->first();
}

$request = Request::create('/api/dashboard/stats', 'GET', [
    'start_date' => '2026-03-31',
    'end_date' => '2026-04-29',
    'opd_id' => '5'
]);

$request->headers->set('Accept', 'application/json');
$request->setUserResolver(fn() => $user);
Auth::setUser($user);

$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
