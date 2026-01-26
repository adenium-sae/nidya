<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSet
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no hay usuario autenticado, continuar sin tenant
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Si ya hay un tenant en sesión, verificar que el usuario tenga acceso
        if (session()->has('tenant_id')) {
            $tenantId = session('tenant_id');
            
            if (!$user->hasAccessToTenant($tenantId)) {
                // El usuario no tiene acceso al tenant actual, limpiar sesión
                session()->forget('tenant_id');
                
                return response()->json([
                    'message' => 'No tienes acceso a este negocio'
                ], 403);
            }
            
            return $next($request);
        }

        // Si no hay tenant en sesión, asignar el primero disponible
        $firstTenant = $user->tenants()->wherePivot('is_active', true)->first();
        
        if (!$firstTenant) {
            return response()->json([
                'message' => 'No perteneces a ningún negocio. Contacta al administrador.'
            ], 403);
        }

        session(['tenant_id' => $firstTenant->id]);
        
        return $next($request);
    }
}