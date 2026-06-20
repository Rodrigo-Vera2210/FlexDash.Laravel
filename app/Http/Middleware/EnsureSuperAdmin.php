<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'superadmin') {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Acceso denegado. Se requiere cuenta de Superadministrador.'], Response::HTTP_FORBIDDEN);
            }
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Se requiere cuenta de Superadministrador.');
        }
        return $next($request);
    }
}
