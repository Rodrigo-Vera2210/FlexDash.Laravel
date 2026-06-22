<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Models\Tax;
use App\Rules\UniqueForCompany;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $category = $request->get('category');
        $lowStock = $request->boolean('low_stock');

        $products = Product::with(['category', 'tax'])
            ->when($search,   fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($category, fn($q) => $q->where('category_id', $category))
            ->when($lowStock,  fn($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'search', 'category', 'lowStock'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $taxes      = Tax::where('is_active', true)->get();
        return view('products.create', compact('categories', 'taxes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'tax_id'        => 'required|exists:taxes,id',
            'code'          => ['required', 'string', 'max:50', new UniqueForCompany('products', 'code')],
            'name'          => 'required|string|max:200',
            'description'   => 'nullable|string',
            'unit'          => 'required|string|max:20',
            'cost'          => 'required|numeric|min:0',
            'price'         => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'image'         => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);
        $product = Product::create($data);

        return redirect()->route('products.show', $product)
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'tax', 'inventoryMovements' => fn($q) => $q->latest()->limit(20)]);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $taxes      = Tax::where('is_active', true)->get();
        return view('products.edit', compact('product', 'categories', 'taxes'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'tax_id'        => 'required|exists:taxes,id',
            'code'          => ['required', 'string', 'max:50', new UniqueForCompany('products', 'code', $product->id)],
            'name'          => 'required|string|max:200',
            'description'   => 'nullable|string',
            'unit'          => 'required|string|max:20',
            'cost'          => 'required|numeric|min:0',
            'price'         => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'is_active'     => 'boolean',
            'image'         => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);
        $product->update($data);

        return redirect()->route('products.show', $product)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado.');
    }
}
