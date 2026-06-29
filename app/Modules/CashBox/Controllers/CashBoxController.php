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
     * Display a list of closed cash box sessions (history).
     */
    public function history(Request $request)
    {
        $query = CashBox::where('status', 'CLOSED')->with(['user', 'branch']);

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->latest('opened_at')->paginate(15);

        return view('cashbox.history', compact('sessions'));
    }

    /**
     * Display details of a closed cash box session.
     */
    public function historyShow(CashBox $cashBox)
    {
        abort_if($cashBox->status !== 'CLOSED', 404);

        $cashBox->load(['user', 'transactions.user']);
        
        $openingBalance = $cashBox->opening_balance;
        $inflows = $cashBox->transactions()->where('type', 'ingreso')->where('concept', '!=', 'Saldo inicial / Apertura de caja')->sum('amount');
        $outflows = $cashBox->transactions()->where('type', 'egreso')->sum('amount');
        $expectedBalance = $cashBox->expected_closing_balance;
        $actualBalance = $cashBox->actual_closing_balance;
        $difference = $cashBox->difference;

        $transactions = $cashBox->transactions()->latest()->paginate(15);

        return view('cashbox.history-show', compact(
            'cashBox', 'openingBalance', 'inflows', 'outflows', 
            'expectedBalance', 'actualBalance', 'difference', 'transactions'
        ));
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

    /**
     * Export cash box transactions to a styled Excel sheet.
     */
    public function exportExcel(CashBox $cashBox)
    {
        $cashBox->load(['transactions.user', 'user']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Caja SESION ' . $cashBox->id);

        // Header style (Navy Blue #0054a6, white text, bold, centered)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0054A6'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Subheader style for Session metadata
        $metaHeaderStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0054A6'],
            ],
        ];

        // Metadata block at the top
        $sheet->setCellValue('A1', 'REPORTE DE CAJA CHICA');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0054A6'));

        $sheet->setCellValue('A3', 'Sesión ID:');
        $sheet->setCellValue('B3', $cashBox->id);
        $sheet->setCellValue('A4', 'Responsable:');
        $sheet->setCellValue('B4', $cashBox->user->name ?? 'Sistema');
        $sheet->setCellValue('A5', 'Estado:');
        $sheet->setCellValue('B5', $cashBox->status);

        $sheet->setCellValue('D3', 'Apertura:');
        $sheet->setCellValue('E3', $cashBox->opened_at ? $cashBox->opened_at->format('d/m/Y H:i') : '—');
        $sheet->setCellValue('D4', 'Cierre:');
        $sheet->setCellValue('E4', $cashBox->closed_at ? $cashBox->closed_at->format('d/m/Y H:i') : '—');
        $sheet->setCellValue('D5', 'Saldo Inicial:');
        $sheet->setCellValue('E5', $cashBox->opening_balance);

        $sheet->getStyle('A3:A5')->applyFromArray($metaHeaderStyle);
        $sheet->getStyle('D3:D5')->applyFromArray($metaHeaderStyle);
        $sheet->getStyle('E5')->getNumberFormat()->setFormatCode('"S/"#,##0.00');

        // Table headers on row 7
        $headers = ['Fecha/Hora', 'Concepto', 'Usuario', 'Tipo', 'Monto'];
        foreach ($headers as $colIndex => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '7', $headerText);
        }
        $sheet->getStyle('A7:E7')->applyFromArray($headerStyle);
        $sheet->getRowDimension(7)->setRowHeight(25);

        // Populate transactions
        $row = 8;
        foreach ($cashBox->transactions as $tx) {
            $sheet->setCellValue('A' . $row, $tx->created_at ? $tx->created_at->format('d/m/Y H:i') : '—');
            $sheet->setCellValue('B' . $row, $tx->concept);
            $sheet->setCellValue('C' . $row, $tx->user->name ?? 'Usuario');
            $sheet->setCellValue('D' . $row, strtoupper($tx->type));
            $sheet->setCellValue('E' . $row, $tx->amount);

            // Styling per type
            $typeColor = $tx->type === 'ingreso' ? '15803D' : 'B91C1C';
            $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($typeColor));
            $sheet->getStyle('E' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($typeColor));
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"S/"#,##0.00');

            $row++;
        }

        // Summary totals at the bottom
        $sheet->setCellValue('D' . $row, 'Saldo Esperado:');
        $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0054A6'));
        $sheet->setCellValue('E' . $row, $cashBox->expected_closing_balance);
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"S/"#,##0.00');

        if ($cashBox->status === 'CLOSED') {
            $row++;
            $sheet->setCellValue('D' . $row, 'Saldo Real:');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0054A6'));
            $sheet->setCellValue('E' . $row, $cashBox->actual_closing_balance);
            $sheet->getStyle('E' . $row)->getFont()->setBold(true);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"S/"#,##0.00');

            $row++;
            $sheet->setCellValue('D' . $row, 'Diferencia:');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0054A6'));
            $sheet->setCellValue('E' . $row, $cashBox->difference);
            $sheet->getStyle('E' . $row)->getFont()->setBold(true);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"S/"#,##0.00');
        }

        // Border styling for the table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
        $sheet->getStyle('A7:E' . $row)->applyFromArray($borderStyle);

        // Autofit columns
        foreach (range(1, 5) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Return download stream
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = "reporte-caja-chica-SESION-{$cashBox->id}.xlsx";

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
