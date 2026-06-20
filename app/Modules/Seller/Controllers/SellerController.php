<?php

namespace App\Modules\Seller\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Seller\Services\SellerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller
{
    public function __construct(
        protected SellerService $sellerService
    ) {}

    public function index()
    {
        $company = auth()->user()->company;
        $sellers = User::where('company_id', $company->id)
            ->where('role', 'vendedor')
            ->get();

        return view('sellers.index', compact('sellers', 'company'));
    }

    public function create()
    {
        return view('sellers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $company = auth()->user()->company;

        try {
            $this->sellerService->createSeller($data, $company);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('sellers.index')->with('status', 'Vendedor creado con éxito.');
    }

    public function toggleStatus(User $seller)
    {
        $admin = auth()->user();
        if ($seller->company_id !== $admin->company_id || $seller->role !== 'vendedor') {
            abort(403);
        }

        if ($seller->status === 'active') {
            $seller->status = 'inactive';
            $seller->save();
        } else {
            if ($this->sellerService->checkLimitReached($admin->company)) {
                return redirect()->back()->withErrors([
                    'limit' => 'No se puede activar el vendedor: Se ha alcanzado el límite de su plan de suscripción.',
                ]);
            }
            $seller->status = 'active';
            $seller->save();
        }

        return redirect()->route('sellers.index')->with('status', 'Estado del vendedor actualizado.');
    }
}
