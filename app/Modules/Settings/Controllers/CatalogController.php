<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Modules\Product\Models\Category;
use App\Models\PaymentMethod;
use App\Rules\UniqueForCompany;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index()
    {
        $taxes = Tax::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        return view('settings.catalogs.index', compact('taxes', 'categories', 'paymentMethods'));
    }

    public function storeTax(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'code' => ['required', 'string', 'max:10', new UniqueForCompany('taxes', 'code')],
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $data['is_active'] = true;
        $tax = Tax::create($data);

        if ($request->expectsJson()) {
            return response()->json($tax, 201);
        }

        return redirect()->route('settings.catalogs.index')->with('success', 'Impuesto creado correctamente.');
    }

    public function updateTax(Request $request, Tax $tax)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'code' => ['required', 'string', 'max:10', new UniqueForCompany('taxes', 'code', $tax->id)],
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $tax->update($data);

        return redirect()->route('settings.catalogs.index')->with('success', 'Impuesto actualizado correctamente.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new UniqueForCompany('categories', 'name')],
            'description' => 'nullable|string|max:255',
        ]);

        $data['is_active'] = true;
        $category = Category::create($data);

        if ($request->expectsJson()) {
            return response()->json($category, 201);
        }

        return redirect()->route('settings.catalogs.index')->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new UniqueForCompany('categories', 'name', $category->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $category->update($data);

        return redirect()->route('settings.catalogs.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function storePaymentMethod(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', new UniqueForCompany('payment_methods', 'name')],
            'description' => 'nullable|string|max:255',
        ]);

        $data['is_active'] = true;
        $method = PaymentMethod::create($data);

        if ($request->expectsJson()) {
            return response()->json($method, 201);
        }

        return redirect()->route('settings.catalogs.index')->with('success', 'Método de pago creado correctamente.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', new UniqueForCompany('payment_methods', 'name', $paymentMethod->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $paymentMethod->update($data);

        return redirect()->route('settings.catalogs.index')->with('success', 'Método de pago actualizado correctamente.');
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'model' => 'required|string|in:tax,category,payment_method',
            'id' => 'required|integer',
        ]);

        $modelName = $request->get('model');
        $id = $request->get('id');

        switch ($modelName) {
            case 'tax':
                $record = Tax::findOrFail($id);
                break;
            case 'category':
                $record = Category::findOrFail($id);
                break;
            case 'payment_method':
                $record = PaymentMethod::findOrFail($id);
                break;
            default:
                return response()->json(['error' => 'Model not supported'], 400);
        }

        $record->is_active = !$record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active
        ]);
    }

    public function destroy($type, $id)
    {
        switch ($type) {
            case 'taxes':
                $tax = Tax::findOrFail($id);
                if ($tax->products()->exists() || $tax->sales()->exists() || $tax->purchases()->exists()) {
                    return redirect()->back()->with('error', 'No se puede eliminar el impuesto porque tiene productos, ventas o compras asociadas. En su lugar, puede desactivarlo.');
                }
                $tax->delete();
                $name = 'Impuesto';
                break;

            case 'categories':
                $category = Category::findOrFail($id);
                if ($category->products()->exists()) {
                    return redirect()->back()->with('error', 'No se puede eliminar la categoría porque tiene productos asociados. En su lugar, puede desactivarla.');
                }
                $category->delete();
                $name = 'Categoría';
                break;

            case 'payment-methods':
                $method = PaymentMethod::findOrFail($id);
                if ($method->payments()->exists()) {
                    return redirect()->back()->with('error', 'No se puede eliminar el método de pago porque tiene pagos asociados. En su lugar, puede desactivarlo.');
                }
                $method->delete();
                $name = 'Método de pago';
                break;

            default:
                return redirect()->back()->with('error', 'Tipo de catálogo no válido.');
        }

        return redirect()->route('settings.catalogs.index')->with('success', "$name eliminado correctamente.");
    }
}
