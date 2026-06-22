<?php

namespace App\Modules\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Partner\Models\Partner;
use App\Rules\UniqueForCompany;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $type    = $request->get('type', 'cliente');
        $search  = $request->get('search');

        $partners = Partner::when($search, fn($q) => $q
            ->where('business_name', 'like', "%{$search}%")
            ->orWhere('document_number', 'like', "%{$search}%"))
            ->when($type === 'cliente',    fn($q) => $q->clientes())
            ->when($type === 'proveedor',  fn($q) => $q->proveedores())
            ->orderBy('business_name')
            ->paginate(15)
            ->withQueryString();

        return view('partners.index', compact('partners', 'type', 'search'));
    }

    public function create()
    {
        return view('partners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'            => 'required|in:cliente,proveedor,ambos',
            'business_name'   => 'required|string|max:200',
            'trade_name'      => 'nullable|string|max:200',
            'document_type'   => 'required|in:RUC,DNI,CE',
            'document_number' => ['required', 'string', 'max:20', new UniqueForCompany('partners', 'document_number')],
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'credit_limit'    => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $partner = Partner::create($data);

        return redirect()->route('partners.show', $partner)
            ->with('success', 'Partner creado correctamente.');
    }

    public function show(Partner $partner)
    {
        $partner->load(['sales' => fn($q) => $q->latest()->limit(10), 'purchases' => fn($q) => $q->latest()->limit(10)]);
        return view('partners.show', compact('partner'));
    }

    public function edit(Partner $partner)
    {
        return view('partners.edit', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'type'          => 'required|in:cliente,proveedor,ambos',
            'document_type'   => 'sometimes|required|in:RUC,DNI,CE',
            'document_number' => ['sometimes', 'required', 'string', 'max:20', new UniqueForCompany('partners', 'document_number', $partner->id)],
            'business_name' => 'required|string|max:200',
            'trade_name'    => 'nullable|string|max:200',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'credit_limit'  => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        $partner->update($data);

        return redirect()->route('partners.show', $partner)
            ->with('success', 'Partner actualizado correctamente.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect()->route('partners.index')->with('success', 'Partner eliminado.');
    }
}
