<?php

namespace App\Http\Controllers;

use App\Modules\Branch\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchSessionController extends Controller
{
    public function setActiveBranch(Request $request)
    {
        $request->validate([
            'active_branch_id' => ['required', 'exists:branches,id,company_id,' . Auth::user()->company_id],
        ]);

        session(['active_branch_id' => (int)$request->active_branch_id]);

        return back()->with('status', 'Sucursal activa actualizada.');
    }
}
