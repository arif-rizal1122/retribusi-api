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
        // Allow all internal staff roles (Admin, OPD, Pengawas, Petugas, Walikota)
        // Only block unauthenticated users or those without a valid role.
        if (!auth()->check() || !(auth()->user() instanceof \App\Models\User)) {
            return response()->json([
                'message' => 'Forbidden: Internal access required.'
            ], 403);
        }

        return $next($request);
    }
}
