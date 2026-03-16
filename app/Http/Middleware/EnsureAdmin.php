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

        // Allow only internal staff roles (App\Models\User)
        if (!auth()->check() || !($user instanceof \App\Models\User)) {
            return response()->json([
                'message' => 'Forbidden: Internal staff access required.'
            ], 403);
        }

        return $next($request);
    }
}
