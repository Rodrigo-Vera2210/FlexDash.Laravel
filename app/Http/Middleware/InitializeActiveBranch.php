<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InitializeActiveBranch
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role !== 'superadmin') {
                // Auto-heal legacy users without default branch_id
                if (is_null($user->branch_id)) {
                    $matrizBranch = \Illuminate\Support\Facades\DB::table('branches')
                        ->where('company_id', $user->company_id)
                        ->orderBy('id')
                        ->first();

                    if ($matrizBranch) {
                        $user->branch_id = $matrizBranch->id;
                        $user->save();
                    }
                }

                $activeBranchId = session('active_branch_id');
                
                // If not set, default to user's branch
                if (is_null($activeBranchId)) {
                    session(['active_branch_id' => $user->branch_id]);
                } else {
                    // Check if current active branch still belongs to user's company (security guard)
                    $exists = \Illuminate\Support\Facades\DB::table('branches')
                        ->where('id', $activeBranchId)
                        ->where('company_id', $user->company_id)
                        ->exists();

                    if (!$exists) {
                        session(['active_branch_id' => $user->branch_id]);
                    }
                }
            }
        }

        return $next($request);
    }
}
