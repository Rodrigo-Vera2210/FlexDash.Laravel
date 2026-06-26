<?php

namespace App\Modules\Branch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('establishment_code')->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'address'            => ['nullable', 'string', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'establishment_code' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        // Check plan limit
        $company = auth()->user()->company;
        $activeBranchCount = Branch::where('is_active', true)->count();

        if ($activeBranchCount >= $company->max_branches) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Has alcanzado el límite de locales ({$company->max_branches}) permitido en tu plan. Actualiza tu suscripción para agregar más.");
        }

        $data['is_active'] = $request->has('is_active') ? true : false;

        DB::transaction(function () use ($data) {
            $branch = Branch::create($data);

            // Seed branch_product entries with stock=0 for all existing products
            $products = Product::all();
            $pivotData = [];
            foreach ($products as $product) {
                $pivotData[$product->id] = ['stock' => 0];
            }
            $branch->products()->attach($pivotData);
        });

        return redirect()->route('branches.index')
            ->with('success', "Local '{$data['name']}' creado con éxito.");
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'address'            => ['nullable', 'string', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'establishment_code' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active') ? true : false;

        $branch->update($data);

        return redirect()->route('branches.index')
            ->with('success', "Local '{$data['name']}' actualizado con éxito.");
    }

    public function destroy(Branch $branch)
    {
        $name = $branch->name;
        $branch->update(['is_active' => false]);

        return redirect()->route('branches.index')
            ->with('success', "Local '{$name}' desactivado con éxito.");
    }
}
