<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante Electrónico - {{ $invoice->sequence }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .container {
            width: 100%;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-border {
            border: 1px solid #cccccc;
        }
        .table-border th, .table-border td {
            border: 1px solid #cccccc;
            padding: 5px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .header-panel {
            border: 1px solid #cccccc;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #ffffff;
        }
        .logo-placeholder {
            font-size: 20px;
            font-weight: bold;
            color: #0D1E36;
            margin-bottom: 15px;
        }
        .access-key {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .w-50 {
            width: 50%;
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- Bloque de Encabezado Principal (Emisor vs Info SRI) -->
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <!-- Lado Izquierdo: Emisor -->
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <div class="header-panel" style="min-height: 180px;">
                    <div class="logo-placeholder">
                        {{ $company ? strtoupper($company->name) : 'FLEXDASH SaaS' }}
                    </div>
                    <div style="font-size: 11px; line-height: 1.4;">
                        <span class="font-bold">{{ $company ? strtoupper($company->name) : 'FLEXDASH POS' }}</span><br>
                        <span class="font-bold">Matriz:</span> {{ $company ? ($company->legal_address ?? $company->address ?? 'QUITO') : 'QUITO' }}<br>
                        <span class="font-bold">Sucursal:</span> {{ $company ? ($company->address ?? 'QUITO') : 'QUITO' }}<br>
                        <span class="font-bold">Obligado a llevar contabilidad:</span> NO
                    </div>
                </div>
            </td>
            <!-- Lado Derecho: Info SRI y Clave de Acceso -->
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <div class="header-panel" style="min-height: 180px;">
                    <div class="title">R.U.C.: {{ $company ? $company->tax_id : '9999999999999' }}</div>
                    <div class="title" style="color: #0A7EA5;">FACTURA</div>
                    <div style="font-size: 11px; line-height: 1.4; margin-bottom: 5px;">
                        <span class="font-bold">No.:</span> {{ $invoice->sequence }}<br>
                        <span class="font-bold">NÚMERO DE AUTORIZACIÓN:</span><br>
                        <span style="font-size: 10px;">{{ $invoice->access_key }}</span><br>
                        <span class="font-bold">FECHA Y HORA DE AUTORIZACIÓN:</span> {{ $invoice->authorized_at ? $invoice->authorized_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}<br>
                        <span class="font-bold">AMBIENTE:</span> {{ strtoupper($invoice->status === 'authorized' ? 'PRODUCCION' : 'PRUEBAS') }}<br>
                        <span class="font-bold">EMISIÓN:</span> NORMAL
                    </div>
                    <div style="margin-top: 8px;">
                        <span class="font-bold block">CLAVE DE ACCESO:</span><br>
                        <span class="access-key block" style="font-size: 9px; font-weight: bold; background-color: #f2f2f2; padding: 2px;">{{ $invoice->access_key }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Bloque de Información del Comprador -->
    <div class="header-panel" style="margin-bottom: 15px;">
        <table style="margin-bottom: 0;">
            <tr>
                <td style="width: 65%;">
                    <span class="font-bold">Razón Social / Nombres y Apellidos:</span> {{ $buyer ? strtoupper($buyer->business_name) : 'CONSUMIDOR FINAL' }}
                </td>
                <td style="width: 35%;">
                    <span class="font-bold">Identificación:</span> {{ $buyer ? $buyer->document_number : '9999999999999' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="font-bold">Fecha Emisión:</span> {{ Carbon\Carbon::parse($sale->issue_date ?? $sale->created_at)->format('d/m/Y') }}
                </td>
                <td>
                    <span class="font-bold">Dirección:</span> {{ $buyer->address ?? 'QUITO' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de Detalles / Items -->
    <table class="table-border" style="margin-bottom: 15px;">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-center">Cod. Principal</th>
                <th style="width: 8%;" class="text-center">Cant.</th>
                <th style="width: 50%;">Descripción</th>
                <th style="width: 15%;" class="text-right">Precio Unitario</th>
                <th style="width: 15%;" class="text-right">Precio Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $qty = (float)$item->quantity;
                    $price = (float)($item->unit_price ?? $item->price ?? 0);
                    $subtotal = round($qty * $price, 2);
                @endphp
                <tr>
                    <td class="text-center font-mono" style="font-size: 10px;">{{ $item->product ? $item->product->code : 'SERV' }}</td>
                    <td class="text-center">{{ number_format($qty, 2) }}</td>
                    <td>{{ strtoupper($item->product ? $item->product->name : ($item->description ?? 'SERVICIO')) }}</td>
                    <td class="text-right font-mono">{{ number_format($price, 2) }}</td>
                    <td class="text-right font-mono">{{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Bloque de Forma de Pago y Totales -->
    <table style="margin-bottom: 0;">
        <tr>
            <!-- Forma de pago (Lado izquierdo) -->
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                <div class="header-panel" style="min-height: 80px;">
                    <div class="font-bold" style="margin-bottom: 5px;">Forma de Pago</div>
                    <table style="font-size: 10px; margin-bottom: 0;">
                        <tr>
                            <td style="border-bottom: 1px solid #eeeeee; padding: 3px 0;">
                                @php
                                    $paymentMethodName = 'SIN UTILIZACIÓN DEL SISTEMA FINANCIERO (EFECTIVO)';
                                    if ($sale->payments && $sale->payments->count() > 0) {
                                        $firstPayment = $sale->payments->first();
                                        $methodName = strtoupper($firstPayment->paymentMethod?->name ?? '');
                                        if (str_contains($methodName, 'TARJETA') || str_contains($methodName, 'CREDITO') || str_contains($methodName, 'DEBITO') || str_contains($methodName, 'BANCO')) {
                                            $paymentMethodName = 'OTROS CON UTILIZACIÓN DEL SISTEMA FINANCIERO';
                                        }
                                    }
                                    $amountTotal = (float)($sale->total_amount ?? $sale->total ?? 0);
                                @endphp
                                {{ $paymentMethodName }}
                            </td>
                            <td class="text-right font-mono" style="border-bottom: 1px solid #eeeeee; padding: 3px 0; width: 30%;">
                                ${{ number_format($amountTotal, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>

                @if($buyer && $buyer->email)
                    <div style="font-size: 9px; color: #666666; margin-top: 5px;">
                        <span class="font-bold">Información Adicional:</span><br>
                        Email: {{ $buyer->email }}
                    </div>
                @endif
            </td>
            <!-- Totales (Lado derecho) -->
            <td style="width: 45%; vertical-align: top;">
                <table class="table-border font-mono" style="font-size: 10px;">
                    @php
                        $subtotalSinImpuestos = 0;
                        $totalIva12 = 0;
                        $totalIva0 = 0;
                        
                        foreach($items as $item) {
                            $qty = (float)$item->quantity;
                            $price = (float)($item->unit_price ?? $item->price ?? 0);
                            $subtotalItem = round($qty * $price, 2);
                            $subtotalSinImpuestos += $subtotalItem;
                            
                            $taxRate = 12.0;
                            $taxModel = $item->tax ?? ($item->product ? $item->product->tax : null);
                            if ($taxModel) {
                                $taxRate = (float)$taxModel->rate;
                            }
                            
                            $taxVal = round($subtotalItem * ($taxRate / 100), 2);
                            if ($taxRate > 0) {
                                $totalIva12 += $taxVal;
                            } else {
                                $totalIva0 += $taxVal;
                            }
                        }
                        
                        $importeTotal = $subtotalSinImpuestos + $totalIva12;
                    @endphp
                    <tr>
                        <td class="font-bold">SUBTOTAL 12%</td>
                        <td class="text-right">{{ number_format($subtotalSinImpuestos, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL 0%</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL No Objeto de IVA</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL SIN IMPUESTOS</td>
                        <td class="text-right">{{ number_format($subtotalSinImpuestos, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">TOTAL Descuento</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        <td class="font-bold">IVA 12%</td>
                        <td class="text-right">{{ number_format($totalIva12, 2) }}</td>
                    </tr>
                    <tr style="font-size: 11px; background-color: #f2f2f2;">
                        <td class="font-bold">VALOR TOTAL</td>
                        <td class="text-right font-bold">${{ number_format($importeTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
