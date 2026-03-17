<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Allow only internal staff roles with admin-level access
        if (!auth()->check() || !($user instanceof \App\Models\User)) {
            return response()->json([
                'message' => 'Forbidden: Internal staff access required.'
            ], 403);
        }

        // Allow all internal staff roles defined in User model constants
        $allowedRoles = [
            \App\Models\User::ROLE_SUPER_ADMIN,
            \App\Models\User::ROLE_ADMIN,
            \App\Models\User::ROLE_OPD,
            \App\Models\User::ROLE_PENGAWAS,
            \App\Models\User::ROLE_KABID_PENGAWAS,
            \App\Models\User::ROLE_KASUBID_PENGAWAS,
            \App\Models\User::ROLE_PETUGAS,
            \App\Models\User::ROLE_WALIKOTA,
            'verifikator', // Legacy/other roles
            'viewer'
        ];
        
        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
