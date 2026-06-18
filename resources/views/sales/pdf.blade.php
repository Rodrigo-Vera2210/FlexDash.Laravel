<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Venta - {{ $sale->series }}-{{ $sale->number }}</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
            padding: 0px;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2D3748;
            background-color: #ffffff;
            font-size: 11px;
        }
        .header-banner {
            position: relative;
            background-color: #0054a6;
            color: #ffffff;
            height: 120px;
            width: 100%;
        }
        .header-banner-table {
            width: 100%;
            padding: 30px 40px 10px 40px;
            border-collapse: collapse;
        }
        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .brand-title {
            font-size: 18px;
            font-weight: bold;
        }
        .brand-subtitle {
            font-size: 9px;
            opacity: 0.85;
            display: block;
        }
        .info-table {
            width: 100%;
            margin: 30px 40px 10px 40px;
            border-collapse: collapse;
        }
        .info-header {
            color: #0054a6;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            padding-bottom: 5px;
        }
        .items-table {
            width: 90%;
            margin: 20px 40px;
            border-collapse: collapse;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            text-transform: uppercase;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 8px 10px;
            border: 1px solid #E2E8F0;
        }
        .totals-table {
            width: 35%;
            margin-right: 40px;
            float: right;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #E2E8F0;
        }
        .totals-table tr.total-row td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #0f172a;
        }
        .payment-info {
            width: 45%;
            margin-left: 40px;
            float: left;
            font-size: 10px;
            line-height: 1.5;
        }
        .terms-section {
            width: 90%;
            margin: 20px 40px;
            clear: both;
            font-size: 9px;
            color: #718096;
            line-height: 1.4;
        }
        .signature-section {
            width: 90%;
            margin: 10px 40px 40px 40px;
            text-align: right;
        }
        .signature-line {
            display: inline-block;
            border-top: 1px solid #A0AEC0;
            width: 200px;
            padding-top: 5px;
            text-align: center;
            font-weight: bold;
            color: #4A5568;
        }
        .footer-banner {
            position: absolute;
            bottom: 0px;
            left: 0px;
            height: 60px;
            width: 100%;
            background-color: #0054a6;
        }
    </style>
</head>
<body>

    <!-- Header waves design -->
    <div class="header-banner">
        <table class="header-banner-table">
            <tr>
                <td>
                    <span class="invoice-title">VENTA / FACTURA</span>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span class="brand-title">FLEXDASH ERP</span>
                    <span class="brand-subtitle">Solución de Gestión de Ventas</span>
                </td>
            </tr>
        </table>
        
        <!-- Bottom wave curve -->
        <svg viewBox="0 0 100 10" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 25px;">
            <path d="M0,10 C30,2 70,18 100,10 L100,10 L100,0 L0,0 Z" fill="#ffffff"/>
        </svg>
    </div>

    <!-- Info Sections -->
    <table class="info-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="info-header">Facturado A:</div>
                <strong style="font-size: 12px; color: #1A202C;">{{ $sale->partner->business_name }}</strong><br>
                @if($sale->partner->trade_name && $sale->partner->trade_name !== $sale->partner->business_name)
                    <span>({{ $sale->partner->trade_name }})</span><br>
                @endif
                <span>{{ $sale->partner->document_type }}: {{ $sale->partner->document_number }}</span><br>
                @if($sale->partner->phone)
                    <span>Teléfono: {{ $sale->partner->phone }}</span><br>
                @endif
                @if($sale->partner->email)
                    <span>Email: {{ $sale->partner->email }}</span><br>
                @endif
                @if($sale->partner->address)
                    <span>Dirección: {{ $sale->partner->address }} {{ $sale->partner->city ? '(' . $sale->partner->city . ')' : '' }}</span>
                @endif
            </td>
            <td style="width: 35%; vertical-align: top;">
                <div class="info-header">Detalle:</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 2px 0;"><strong>Comprobante:</strong></td>
                        <td style="text-align: right; font-family: monospace;">{{ $sale->series }}-{{ $sale->number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;"><strong>Fecha Emisión:</strong></td>
                        <td style="text-align: right;">{{ $sale->issue_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;"><strong>Vencimiento:</strong></td>
                        <td style="text-align: right;">{{ $sale->due_date ? $sale->due_date->format('d/m/Y') : 'Al contado' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;"><strong>Estado:</strong></td>
                        <td style="text-align: right; font-weight: bold; color: #2B6CB0;">{{ $sale->status }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">SL</th>
                <th style="width: 12%;">Código</th>
                <th style="width: 48%;">Descripción</th>
                <th style="text-align: right; width: 12%;">Precio</th>
                <th style="text-align: right; width: 8%;">Cant.</th>
                <th style="text-align: right; width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->details as $index => $detail)
                <tr style="background-color: {{ $index % 2 === 0 ? '#ffffff' : '#F7FAFC' }};">
                    <td style="font-weight: bold; color: #718096;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td style="font-family: monospace; color: #718096;">{{ $detail->product->code }}</td>
                    <td>
                        <strong>{{ $detail->product->name }}</strong>
                        @if($detail->notes)
                            <br><span style="font-size: 8px; color: #718096;">{{ $detail->notes }}</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-family: monospace;">S/ {{ number_format($detail->unit_price, 2) }}</td>
                    <td style="text-align: right; font-family: monospace;">{{ number_format($detail->quantity, 0) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold;">S/ {{ number_format($detail->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Bottom Financial Summary and Payment Info -->
    <div>
        <!-- Payment Info (Left) -->
        <div class="payment-info">
            <div style="font-weight: bold; color: #0054a6; margin-bottom: 5px; text-transform: uppercase;">Información de Pago</div>
            @if($sale->payments->isNotEmpty())
                <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                    <tr style="border-bottom: 1px solid #E2E8F0; font-weight: bold;">
                        <td style="padding: 2px 0;">Fecha</td>
                        <td style="padding: 2px 0;">Método</td>
                        <td style="padding: 2px 0; text-align: right;">Monto</td>
                    </tr>
                    @foreach($sale->payments as $payment)
                        <tr>
                            <td style="padding: 2px 0;">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td style="padding: 2px 0;">{{ $payment->paymentMethod->name }}</td>
                            <td style="padding: 2px 0; text-align: right; font-family: monospace;">S/ {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <span style="color: #718096; italic">Pendiente de registro de pagos.</span>
            @endif
        </div>

        <!-- Totals (Right) -->
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right; font-family: monospace;">S/ {{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Impuesto ({{ $sale->tax->name ?? 'IGV' }}):</td>
                <td style="text-align: right; font-family: monospace;">S/ {{ number_format($sale->tax_amount, 2) }}</td>
            </tr>
            @if($sale->discount > 0)
                <tr>
                    <td style="color: #E53E3E;">Descuento:</td>
                    <td style="text-align: right; font-family: monospace; color: #E53E3E;">-S/ {{ number_format($sale->discount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>Total General:</td>
                <td style="text-align: right; font-family: monospace;">S/ {{ number_format($sale->total, 2) }}</td>
            </tr>
            <tr>
                <td style="font-size: 10px; color: #4A5568;">Cobrado:</td>
                <td style="text-align: right; font-family: monospace; color: #2F855A; font-weight: bold;">S/ {{ number_format($sale->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="font-size: 10px; color: #4A5568;">Pendiente:</td>
                <td style="text-align: right; font-family: monospace; color: #C53030; font-weight: bold;">S/ {{ number_format($sale->pending_balance, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Signature (if approved) -->
    <div style="clear: both; height: 10px;"></div>
    
    <div class="signature-section">
        <br><br><br>
        <div class="signature-line">
            Firma Autorizada
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="terms-section">
        <strong>Términos & Condiciones:</strong><br>
        1. Toda reclamación sobre este comprobante de venta deberá realizarse dentro de las 48 horas de su emisión.<br>
        2. Los pagos al crédito están sujetos a intereses por mora en caso de no cumplirse la fecha establecida.<br>
        3. Esta representación impresa no exime al cliente de la validación tributaria correspondiente.
    </div>

    <!-- Footer Banner -->
    <div class="footer-banner">
        <svg viewBox="0 0 100 10" preserveAspectRatio="none" style="position: absolute; top: -1px; left: 0; width: 100%; height: 20px;">
            <path d="M0,0 C30,8 70,-2 100,0 L100,10 L0,10 Z" fill="#ffffff"/>
        </svg>
        <div style="padding: 25px 40px 10px 40px; font-size: 8px; color: #ffffff; text-align: center; font-weight: bold; letter-spacing: 0.5px;">
            FlexDash ERP · Sistema de Ventas · RUC 20999999999 · Dirección Fiscal: Av. Larco 123, Miraflores, Lima
        </div>
    </div>

</body>
</html>
