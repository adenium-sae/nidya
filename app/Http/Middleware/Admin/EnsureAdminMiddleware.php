<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $token = $user->currentAccessToken();
        if ($token && in_array('admin', $token->abilities ?? [])) {
            return $next($request);
        }
        if ($user->roles()->where('key', 'admin')->exists()) {
            return $next($request);
        }
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
