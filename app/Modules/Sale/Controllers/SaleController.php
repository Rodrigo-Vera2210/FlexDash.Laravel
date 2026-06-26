<?php

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Partner\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Models\Tax;
use App\Services\SaleService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private SaleService    $saleService,
        private PaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        $status  = $request->get('status');
        $search  = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $sales = Sale::with('partner')
            ->when($status,   fn($q) => $q->where('status', $status))
            ->when($search,   fn($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhereHas('partner', fn($q2) => $q2->where('business_name', 'like', "%{$search}%")))
            ->when($dateFrom, fn($q) => $q->where('issue_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->where('issue_date', '<=', $dateTo))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales', 'status', 'search', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        $partners = Partner::clientes()->active()->orderBy('business_name')->get();
        $products = Product::active()->with('tax')->orderBy('name')->get();
        $services = \App\Modules\Service\Models\Service::active()->with('tax')->orderBy('name')->get();
        $taxes    = Tax::where('is_active', true)->get();
        $nextNum  = SaleService::nextNumber();
        return view('sales.create', compact('partners', 'products', 'services', 'taxes', 'nextNum'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'tax_id'     => 'nullable|exists:taxes,id',
            'issue_date' => 'required|date',
            'due_date'   => 'nullable|date|after_or_equal:issue_date',
            'items'      => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id|required_without:items.*.service_id',
            'items.*.service_id' => 'nullable|exists:services,id|required_without:items.*.product_id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Enforce monthly transaction limits
        $company = auth()->user()->company;
        if ($company) {
            $limit = $company->max_monthly_transactions;
            $salesCount = \App\Modules\Sale\Models\Sale::where('company_id', $company->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $purchasesCount = \App\Modules\Purchase\Models\Purchase::where('company_id', $company->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            if (($salesCount + $purchasesCount) >= $limit) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'limit' => "El límite de transacciones mensuales para su suscripción ({$limit}) ha sido alcanzado."
                ]);
            }
        }

        $header = $request->only(['partner_id', 'tax_id', 'issue_date', 'due_date', 'notes', 'discount']);
        $header['user_id']   = auth()->id();
        $header['branch_id'] = auth()->user()->branch_id;
        $header['number']    = SaleService::nextNumber();
        $header['series']  = 'F001';

        $sale = $this->saleService->create($header, $request->items);

        return redirect()->route('sales.show', $sale)
            ->with('success', "Venta #{$sale->number} creada en BORRADOR.");
    }

    public function show(Sale $sale)
    {
        $sale->load(['partner', 'details.product', 'details.service', 'payments.paymentMethod', 'user', 'tax', 'electronicInvoice']);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        return view('sales.show', compact('sale', 'paymentMethods'));
    }

    public function approve(Sale $sale)
    {
        try {
            $this->saleService->approve($sale);
            return redirect()->route('sales.show', $sale)
                ->with('success', "Venta #{$sale->number} aprobada. Stock descontado.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Sale $sale)
    {
        try {
            $this->saleService->cancel($sale, $request->input('reason', ''));
            return redirect()->route('sales.show', $sale)
                ->with('success', "Venta #{$sale->number} anulada.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storePayment(Request $request, Sale $sale)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount'            => 'required|numeric|min:0.01',
            'payment_date'      => 'required|date',
            'reference'         => 'nullable|string|max:100',
        ]);

        try {
            $this->paymentService->register($sale, $request->all());
            return redirect()->route('sales.show', $sale)
                ->with('success', "Pago de S/ {$request->amount} registrado.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function downloadPdf(Sale $sale)
    {
        $sale->load(['partner', 'details.product', 'details.service', 'payments.paymentMethod', 'user', 'tax']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', compact('sale'));
        return $pdf->download("factura-{$sale->series}-{$sale->number}.pdf");
    }
}
