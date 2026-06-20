<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSellerAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && $user->role === 'vendedor') {
            $type = $request->query('type');
            $isBlocked = false;

            // Block dashboard, purchases, products, cashbox, audit, settings, and sellers management
            if ($request->routeIs('dashboard') || $request->is('dashboard') ||
                $request->is('purchases*') || $request->routeIs('purchases*') ||
                $request->is('products*') || $request->routeIs('products*') ||
                $request->is('cashbox*') || $request->routeIs('cashbox*') ||
                $request->is('audit*') || $request->routeIs('audit*') ||
                $request->is('settings*') || $request->routeIs('settings*') ||
                $request->is('sellers*') || $request->routeIs('sellers*')) {
                $isBlocked = true;
            }

            // Block suppliers list and single supplier views/edits
            if ($request->is('partners*') || $request->routeIs('partners*')) {
                if ($type === 'proveedor') {
                    $isBlocked = true;
                }
                
                $partner = $request->route('partner');
                if ($partner) {
                    if (is_numeric($partner)) {
                        $partnerModel = \App\Modules\Partner\Models\Partner::find($partner);
                    } else {
                        $partnerModel = $partner;
                    }
                    if ($partnerModel && $partnerModel->type === 'proveedor') {
                        $isBlocked = true;
                    }
                }
            }

            if ($isBlocked) {
                if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                    return response()->json(['error' => 'Acceso denegado.'], Response::HTTP_FORBIDDEN);
                }
                return redirect()->route('sales.index')->with('error', 'Acceso denegado. Su perfil de Vendedor no tiene permitido ingresar a esta sección.');
            }
        }

        return $next($request);
    }
}
