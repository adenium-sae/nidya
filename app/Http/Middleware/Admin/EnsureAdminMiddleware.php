<?php

namespace App\Http\Middleware\Admin;

use App\Exceptions\Auth\AccessDeniedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMiddleware
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
