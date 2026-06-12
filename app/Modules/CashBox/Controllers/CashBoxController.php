<?php

namespace App\Modules\CashBox\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\CashBox\Services\CashBoxService;
use App\Modules\Partner\Models\Partner;
use App\Models\PaymentMethod;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use Illuminate\Http\Request;

class CashBoxController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct(private CashBoxService $cashBoxService) {}

    /**
     * Display the status of the current open session or show the opening form.
     */
    public function index(Request $request)
    {
        $activeBox = CashBox::active()->first();

        if ($activeBox) {
            $activeBox->load('transactions.user');
            
            // Stats calculations
            $openingBalance = $activeBox->opening_balance;
            $inflows = $activeBox->transactions()->where('type', 'ingreso')->where('concept', '!=', 'Saldo inicial / Apertura de caja')->sum('amount');
            $outflows = $activeBox->transactions()->where('type', 'egreso')->sum('amount');
            $expectedBalance = $activeBox->expected_closing_balance;

            $transactions = $activeBox->transactions()->latest()->paginate(15);

            return view('cashbox.index', compact('activeBox', 'openingBalance', 'inflows', 'outflows', 'expectedBalance', 'transactions'));
        }

        return view('cashbox.index', ['activeBox' => null]);
    }

    /**
     * Open a new cash box register.
     */
    public function open(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:255',
        ]);

        try {
            $this->cashBoxService->openBox($request->opening_balance, $request->notes);
            return redirect()->route('cashbox.index')->with('success', 'Sesión de caja chica abierta.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['balance' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Close the active register session.
     */
    public function close(Request $request)
    {
        $activeBox = CashBox::active()->first();
        if (!$activeBox) {
            return redirect()->back()->with('error', 'No hay ninguna sesión de caja chica activa para cerrar.');
        }

        $request->validate([
            'actual_closing_balance' => 'required|numeric|min:0',
            'notes'                  => 'nullable|string|max:255',
        ]);

        try {
            $this->cashBoxService->closeBox($activeBox, $request->actual_closing_balance, $request->notes);
            return redirect()->route('cashbox.index')->with('success', 'Sesión de caja chica cerrada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Register a manual inflow or outflow.
     */
    public function adjust(Request $request)
    {
        $activeBox = CashBox::active()->first();
        if (!$activeBox) {
            return redirect()->back()->with('error', 'Debe abrir una sesión de caja chica primero.');
        }

        $request->validate([
            'type'    => 'required|in:ingreso,egreso',
            'amount'  => 'required|numeric|min:0.01',
            'concept' => 'required|string|max:255',
        ]);

        try {
            $this->cashBoxService->recordTransaction($activeBox, $request->type, $request->amount, $request->concept);
            return redirect()->route('cashbox.index')->with('success', 'Movimiento registrado con éxito.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Render the single-partner batch payment screen.
     */
    public function batchPaymentForm()
    {
        $activeBox = CashBox::active()->first();
        if (!$activeBox) {
            return redirect()->route('cashbox.index')->with('error', 'Debe abrir una sesión de caja chica para realizar cobros o pagos.');
        }

        $partners = Partner::where('is_active', true)->orderBy('business_name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('cashbox.batch-payment', compact('partners', 'paymentMethods'));
    }

    /**
     * Retrieve pending documents (Sales or Purchases) for a partner via AJAX.
     */
    public function getPendingDocuments(Partner $partner)
    {
        $mode = request()->get('mode', $partner->type === 'proveedor' ? 'proveedor' : 'cliente');
        
        if ($mode === 'proveedor') {
            $documents = Purchase::where('partner_id', $partner->id)
                ->where('status', 'APROBADO')
                ->where('pending_balance', '>', 0)
                ->orderBy('issue_date')
                ->get(['id', 'number', 'issue_date', 'due_date', 'total', 'pending_balance']);
        } else {
            $documents = Sale::where('partner_id', $partner->id)
                ->where('status', 'APROBADO')
                ->where('pending_balance', '>', 0)
                ->orderBy('issue_date')
                ->get(['id', 'number', 'issue_date', 'due_date', 'total', 'pending_balance']);
        }

        return response()->json($documents);
    }

    /**
     * Process batch payment submissions.
     */
    public function storeBatchPayment(Request $request)
    {
        $request->validate([
            'partner_type'      => 'required|in:cliente,proveedor',
            'partner_id'        => 'required|exists:partners,id',
            'document_ids'      => 'required|array|min:1',
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date'      => 'required|date',
            'reference'         => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        try {
            $this->cashBoxService->processBatchPayment(
                $request->partner_type,
                $request->partner_id,
                $request->document_ids,
                $request->amount,
                $request->payment_method_id,
                $request->payment_date,
                $request->reference,
                $request->notes
            );
            return redirect()->route('cashbox.index')->with('success', 'Pago masivo registrado y distribuido correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
