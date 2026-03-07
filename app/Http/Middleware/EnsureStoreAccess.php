<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = 'dashboard.view'): Response
    {
        $user = $request->user();
        $storeId = $request->header('X-Store-Id') ?? $request->input('store_id') ?? $request->route('store');

        if (!$user) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'No autenticado']], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$storeId) {
            // If no store is specified, we might want to allow it if it's a general list request,
            // but usually, operational requests need a store.
            // For now, let's just proceed and let the Scopes handle the filtering if it's a list.
            return $next($request);
        }

        if (!$user->hasPermissionInStore($permission, $storeId)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN_STORE_ACCESS',
                    'message' => 'No tienes permiso para acceder a esta tienda o realizar esta acción.'
                ]
            ], 403);
        }

        return $next($request);
    }
}
