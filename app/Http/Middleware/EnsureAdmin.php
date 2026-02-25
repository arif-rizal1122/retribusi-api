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
        // Only allow users that are authenticated and are instances of the User model (Admin/Petugas)
        if (!auth()->check() || !(auth()->user() instanceof \App\Models\User)) {
            return response()->json([
                'message' => 'Forbidden: Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
