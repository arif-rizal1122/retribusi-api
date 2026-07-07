<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoDeductAccess
{
    public function handle(Request $request, Closure $next, string $actionType = 'read'): Response
    {
        if ($actionType === 'webhook') {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        switch ($actionType) {
            case 'admin':
                $allowed = ['super_admin', 'admin'];
                if (!in_array($user->role, $allowed)) {
                    return response()->json([
                        'message' => 'Forbidden: Hanya super admin dan admin'
                    ], 403);
                }
                break;

            case 'write':
                $allowed = ['super_admin', 'admin', 'opd', 'petugas'];
                if (!in_array($user->role, $allowed)) {
                    return response()->json([
                        'message' => 'Forbidden: Tidak punya wewenang write'
                    ], 403);
                }
                break;

            default:
                $allowed = [
                    'super_admin', 'admin', 'opd', 'petugas',
                    'pengawas', 'kabid_pengawas', 'kasubid_pengawas',
                    'viewer', 'wajib_pajak', 'citizen', 'verifikator'
                ];
                if (!in_array($user->role, $allowed)) {
                    return response()->json([
                        'message' => 'Forbidden: Tidak punya akses'
                    ], 403);
                }
                break;
        }

        return $next($request);
    }
}
