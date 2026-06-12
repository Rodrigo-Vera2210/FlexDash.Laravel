<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Product;
use App\Modules\Purchase\Models\Purchase;
use App\Models\Tax;
use App\Services\PurchaseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService,
        private PaymentService  $paymentService,
    ) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $purchases = Purchase::with('partner')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhereHas('partner', fn($q2) => $q2->where('business_name', 'like', "%{$search}%")))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'status', 'search'));
    }

    public function create()
    {
        $partners = Partner::proveedores()->active()->orderBy('business_name')->get();
        $products = Product::active()->with('tax')->orderBy('name')->get();
        $taxes    = Tax::where('is_active', true)->get();
        $nextNum  = PurchaseService::nextNumber();
        return view('purchases.create', compact('partners', 'products', 'taxes', 'nextNum'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'issue_date' => 'required|date',
            'items'      => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ]);

        $header = $request->only(['partner_id', 'tax_id', 'issue_date', 'due_date', 'supplier_invoice', 'notes', 'discount']);
        $header['user_id'] = auth()->id();
        $header['number']  = PurchaseService::nextNumber();
        $header['series']  = 'C001';

        $purchase = $this->purchaseService->create($header, $request->items);

        return redirect()->route('purchases.show', $purchase)
            ->with('success', "Compra #{$purchase->number} creada en BORRADOR.");
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['partner', 'details.product', 'payments.paymentMethod', 'user', 'tax']);
        return view('purchases.show', compact('purchase'));
    }

    public function approve(Purchase $purchase)
    {
        try {
            $this->purchaseService->approve($purchase);
            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Compra #{$purchase->number} aprobada. Stock actualizado.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Purchase $purchase)
    {
        try {
            $this->purchaseService->cancel($purchase, $request->input('reason', ''));
            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Compra #{$purchase->number} anulada.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storePayment(Request $request, Purchase $purchase)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount'            => 'required|numeric|min:0.01',
            'payment_date'      => 'required|date',
        ]);

        try {
            $this->paymentService->register($purchase, $request->all());
            return redirect()->route('purchases.show', $purchase)
                ->with('success', "Pago de S/ {$request->amount} registrado.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
