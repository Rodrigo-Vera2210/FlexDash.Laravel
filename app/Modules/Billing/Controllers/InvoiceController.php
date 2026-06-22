<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\ElectronicInvoice;
use App\Modules\Billing\Services\ElectronicInvoicingService;
use App\Modules\Sale\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class InvoiceController extends Controller
{
    public function __construct(
        protected ElectronicInvoicingService $invoicingService
    ) {}

    /**
     * Display a listing of electronic invoices.
     */
    public function index(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company || !$company->has_electronic_billing) {
            return redirect()->route('dashboard')->with('error', 'Su plan de suscripción no incluye acceso al módulo de Facturación Electrónica.');
        }

        $query = ElectronicInvoice::where('company_id', $company->id)
            ->with('invoicable')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('access_key', 'like', "%{$search}%")
                  ->orWhere('sequence', 'like', "%{$search}%");
            });
        }

        $invoices = $query->paginate(15);

        return view('billing.invoices.index', compact('invoices'));
    }

    /**
     * Manually trigger billing for a sale.
     */
    public function store(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company || !$company->has_electronic_billing) {
            return redirect()->route('dashboard')->with('error', 'Su plan de suscripción no incluye acceso al módulo de Facturación Electrónica.');
        }

        $request->validate([
            'sale_id' => 'required|integer',
            'certificate_id' => 'nullable|integer|exists:company_certificates,id'
        ]);

        $sale = Sale::where('company_id', $company->id)->findOrFail($request->get('sale_id'));

        // Check if the sale is paid
        if ($sale->status !== Sale::STATUS_PAID) {
            return redirect()->back()->with('error', 'Solo se pueden facturar electrónicamente ventas que estén totalmente pagadas.');
        }

        $certificateId = $request->get('certificate_id');
        if ($certificateId) {
            $exists = $company->companyCertificates()->where('id', $certificateId)->exists();
            if (!$exists) {
                return redirect()->back()->with('error', 'El certificado seleccionado no pertenece a la empresa.');
            }
        }

        try {
            $this->invoicingService->process($sale, $certificateId);
            return redirect()->back()->with('success', 'Factura electrónica emitida y autorizada correctamente.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al emitir factura: ' . $e->getMessage());
        }
    }

    /**
     * Download authorized XML file.
     */
    public function downloadXml($id)
    {
        $company = auth()->user()->company;
        $isSuperAdmin = auth()->user()->role === 'superadmin';

        $invoice = ElectronicInvoice::findOrFail($id);

        // Access check
        if (!$isSuperAdmin && $invoice->company_id !== $company->id) {
            abort(403, 'Acceso no autorizado.');
        }

        if (!$invoice->xml_path || !Storage::exists($invoice->xml_path)) {
            abort(404, 'Archivo XML no encontrado.');
        }

        return Storage::download($invoice->xml_path, $invoice->access_key . '.xml', [
            'Content-Type' => 'application/xml'
        ]);
    }

    /**
     * Download authorized PDF RIDE file.
     */
    public function downloadPdf($id)
    {
        $company = auth()->user()->company;
        $isSuperAdmin = auth()->user()->role === 'superadmin';

        $invoice = ElectronicInvoice::findOrFail($id);

        // Access check
        if (!$isSuperAdmin && $invoice->company_id !== $company->id) {
            abort(403, 'Acceso no autorizado.');
        }

        if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
            abort(404, 'Archivo PDF no encontrado.');
        }

        return Storage::download($invoice->pdf_path, 'factura_' . $invoice->sequence . '.pdf', [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
