<?php

namespace App\Modules\Billing\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Exception;

class RideGeneratorService
{
    /**
     * Generate and save RIDE PDF for an electronic invoice.
     *
     * @param object $invoice The ElectronicInvoice model instance.
     * @return string The storage path of the generated PDF file.
     * @throws Exception
     */
    public function generateRidePdf(object $invoice): string
    {
        $sale = $invoice->invoicable; // Morph relation
        if (!$sale) {
            throw new Exception("El comprobante electrónico no tiene una venta o pago asociado.");
        }

        $company = $invoice->company;
        $buyer = $sale->partner;

        // Extract products items details
        $items = $sale->saleDetails ?? $sale->details ?? [];

        // Format and render PDF using Barryvdh\DomPDF
        $pdf = Pdf::loadView('billing.invoices.ride', [
            'invoice' => $invoice,
            'sale'    => $sale,
            'company' => $company,
            'buyer'   => $buyer,
            'items'   => $items
        ]);

        $pdfBinary = $pdf->output();

        $fileName = 'ride_' . $invoice->id . '_' . time() . '.pdf';
        $securePath = 'secure_invoices/' . $fileName;

        Storage::put($securePath, $pdfBinary);

        return $securePath;
    }
}
