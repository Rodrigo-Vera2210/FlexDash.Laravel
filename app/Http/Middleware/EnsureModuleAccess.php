<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = auth()->user();
        
        // Superadmin bypasses module restrictions
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        if ($user && $user->company) {
            $company = $user->company;

            // Handle edge case for partners: check if type is cliente or proveedor
            if ($module === 'partners') {
                $type = $request->query('type');
                
                // If checking specific partner model binding
                $partner = $request->route('partner');
                if ($partner) {
                    if (is_numeric($partner)) {
                        $partnerModel = \App\Modules\Partner\Models\Partner::find($partner);
                    } else {
                        $partnerModel = $partner;
                    }
                    if ($partnerModel) {
                        $type = $partnerModel->type; // 'cliente' or 'proveedor'
                    }
                }

                if ($type === 'proveedor') {
                    $module = 'proveedores';
                } else if ($type === 'cliente') {
                    $module = 'clientes';
                } else {
                    // General partners list without specific type
                    // Check if company has access to at least one of them
                    if ($company->hasModuleAccess('clientes') || $company->hasModuleAccess('proveedores')) {
                        return $next($request);
                    }
                    return $this->forbiddenResponse($request, 'clientes');
                }
            }

            if (!$company->hasModuleAccess($module)) {
                return $this->forbiddenResponse($request, $module);
            }
        }

        return $next($request);
    }

    protected function forbiddenResponse(Request $request, string $module): Response
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'error' => "Acceso denegado. Su plan no incluye acceso al módulo de {$module}."
            ], Response::HTTP_FORBIDDEN);
        }

        $moduleNames = [
            'ventas' => 'Ventas',
            'clientes' => 'Clientes',
            'caja_chica' => 'Caja Chica',
            'settings' => 'Configuración/Vendedores',
            'kardex' => 'Kardex (Inventario)',
            'compras' => 'Compras',
            'proveedores' => 'Proveedores',
        ];

        $displayName = $moduleNames[$module] ?? ucfirst($module);

        return redirect()->route('dashboard')
            ->with('error', "Acceso denegado. Su plan o suscripción no incluye acceso al módulo de {$displayName}.");
    }
}
