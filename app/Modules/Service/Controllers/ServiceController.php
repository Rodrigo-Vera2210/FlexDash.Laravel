<?php

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Models\ServiceCategory;
use App\Models\Tax;
use App\Rules\UniqueForCompany;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $category = $request->get('category');

        $services = Service::with(['category', 'tax'])
            ->when($search,   fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($category, fn($q) => $q->where('service_category_id', $category))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();

        return view('services.index', compact('services', 'categories', 'search', 'category'));
    }

    public function create()
    {
        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();
        $taxes      = Tax::where('is_active', true)->get();
        return view('services.create', compact('categories', 'taxes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_category_id' => 'nullable|exists:service_categories,id',
            'tax_id'              => 'nullable|exists:taxes,id',
            'code'                => ['required', 'string', 'max:50', new UniqueForCompany('services', 'code')],
            'name'                => 'required|string|max:200',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'cost'                => 'nullable|numeric|min:0',
        ]);

        $data['cost'] = $data['cost'] ?? 0;
        $service = Service::create($data);

        return redirect()->route('services.show', $service)
            ->with('success', 'Servicio creado correctamente.');
    }

    public function show(Service $service)
    {
        $service->load(['category', 'tax']);
        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();
        $taxes      = Tax::where('is_active', true)->get();
        return view('services.edit', compact('service', 'categories', 'taxes'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'service_category_id' => 'nullable|exists:service_categories,id',
            'tax_id'              => 'nullable|exists:taxes,id',
            'code'                => ['required', 'string', 'max:50', new UniqueForCompany('services', 'code', $service->id)],
            'name'                => 'required|string|max:200',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'cost'                => 'nullable|numeric|min:0',
            'is_active'           => 'boolean',
        ]);

        $data['cost'] = $data['cost'] ?? 0;
        $service->update($data);

        return redirect()->route('services.show', $service)
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service)
    {
        // Check if service is referenced in sale_details
        if ($service->saleDetails()->exists()) {
            // Deactivate instead of delete
            $service->update(['is_active' => false]);
            return redirect()->route('services.index')
                ->with('success', 'El servicio tiene ventas asociadas y fue desactivado en lugar de eliminado.');
        }

        $service->delete();
        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }
}
