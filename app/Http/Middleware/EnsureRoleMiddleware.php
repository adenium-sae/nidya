<?php

namespace App\Http\Middleware;

use App\Exceptions\Auth\AccessDeniedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();
        if (!$user || !$user->roles()->where('key', $type)->exists()) {
            throw new AccessDeniedException();
        }
        return $next($request);
    }
}
