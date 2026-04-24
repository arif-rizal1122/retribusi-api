<?php
/**
 * DEPLOY WEBHOOK RECEIVER
 * Endpoint: /api/admin/deploy-hook
 * Header: X-Deploy-Secret: <secret>
 * 
 * ⚠️ HANYA untuk staging environment. Jangan aktifkan di production.
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeployHookController extends Controller
{
    public function handle(Request $request)
    {
        // Security check
        $secret = $request->header('X-Deploy-Secret');
        $validSecret = config('app.deploy_secret', env('DEPLOY_SECRET', ''));

        if (empty($validSecret) || $secret !== $validSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Only allow in staging/local
        if (app()->environment('production')) {
            return response()->json(['error' => 'Not allowed in production'], 403);
        }

        Log::info('Deploy hook triggered', ['ip' => $request->ip()]);

        $output = [];

        try {
            // Run artisan commands
            Artisan::call('migrate', ['--force' => true]);
            $output[] = 'migrate: ' . Artisan::output();

            Artisan::call('config:cache');
            $output[] = 'config:cache: OK';

            Artisan::call('route:cache');
            $output[] = 'route:cache: OK';

            Artisan::call('view:cache');
            $output[] = 'view:cache: OK';

            // Seed staging users
            Artisan::call('db:seed', ['--class' => 'StagingUserSeeder', '--force' => true]);
            $output[] = 'StagingUserSeeder: ' . Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => '✅ Deploy hook executed successfully',
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Deploy hook failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'output' => $output,
            ], 500);
        }
    }
}
