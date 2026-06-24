<?php

namespace App\Modules\Billing\Services;

use Exception;
use SimpleXMLElement;
use Carbon\Carbon;

class XmlGeneratorService
{
    /**
     * Generate the SRI invoice XML for a sale.
     *
     * @param object $sale The Sale model instance.
     * @param object $config The BillingConfig model instance.
     * @param int $sequenceNumber The sequence number to use.
     * @return string
     * @throws Exception
     */
    public function generateInvoiceXml(object $sale, object $config, int $sequenceNumber): string
    {
        $company = $config->company ?? $sale->company;
        
        $ruc = $company ? $company->tax_id : '9999999999999'; // Fallback if none
        if (strlen($ruc) !== 13) {
            $ruc = str_pad($ruc, 13, '0', STR_PAD_RIGHT);
        }

        $date = Carbon::parse($sale->issue_date ?? $sale->created_at);
        $dateStr = $date->format('d/m/Y');
        $dateAccessKey = $date->format('dmY');

        $envCode = $config->environment === 'produccion' ? '2' : '1';
        $establishment = str_pad($config->establishment, 3, '0', STR_PAD_LEFT);
        $emissionPoint = str_pad($config->emission_point, 3, '0', STR_PAD_LEFT);
        $sequenceStr = str_pad($sequenceNumber, 9, '0', STR_PAD_LEFT);
        $numericCode = '12345678'; // Standard fixed numeric code
        $emissionType = '1'; // Normal offline emission

        // 1. Generate access key
        $accessKeyWithoutCheckDigit = $dateAccessKey . '01' . $ruc . $envCode . $establishment . $emissionPoint . $sequenceStr . $numericCode . $emissionType;
        $checkDigit = $this->calculateModulo11($accessKeyWithoutCheckDigit);
        $accessKey = $accessKeyWithoutCheckDigit . $checkDigit;

        // 2. Map buyer identification type and totals
        $buyerDocType = '07'; // Consumidor Final default
        $buyerId = '9999999999999';
        $buyerName = 'CONSUMIDOR FINAL';
        $buyerEmail = '';

        $totalSinImpuestos = 0;
        $impuestosMap = [];
        $totalFinal = 0;
        $xmlDetails = [];
        $paymentCode = '01'; // Default: Cash/Efectivo
        $paymentName = 'EFECTIVO';

        if ($sale instanceof \App\Models\SubscriptionPayment) {
            // Subscription Billing Details
            $planModel = \App\Models\Plan::where('code', $sale->plan)->first();
            $priceUnit = $planModel ? (float)$planModel->price : 29.90;
            $subtotalItem = $priceUnit;
            $totalSinImpuestos = $subtotalItem;

            $taxRate = 12.0; // standard IVA
            // Try to find any active tax to align rate
            $taxModel = \App\Models\Tax::where('is_active', true)->where('rate', '>', 0)->first();
            if ($taxModel) {
                $taxRate = (float)$taxModel->rate;
            }
            $taxPercentageCode = $taxRate == 15 ? '4' : '2';

            $taxVal = round($subtotalItem * ($taxRate / 100), 2);
            $totalFinal = $subtotalItem + $taxVal;

            $key = '2_' . $taxPercentageCode;
            $impuestosMap[$key] = [
                'codigo' => '2',
                'codigoPorcentaje' => $taxPercentageCode,
                'tarifa' => number_format($taxRate, 2, '.', ''),
                'baseImponible' => $subtotalItem,
                'valor' => $taxVal
            ];

            $xmlDetails[] = [
                'codigoPrincipal' => 'PLAN',
                'descripcion' => 'PLAN DE SUSCRIPCION ' . strtoupper($sale->plan),
                'cantidad' => '1.0000',
                'precioUnitario' => number_format($priceUnit, 4, '.', ''),
                'descuento' => '0.0000',
                'precioTotalSinImpuesto' => number_format($subtotalItem, 2, '.', ''),
                'tax_codigo' => '2',
                'tax_codigoPorcentaje' => $taxPercentageCode,
                'tax_tarifa' => number_format($taxRate, 2, '.', ''),
                'tax_baseImponible' => number_format($subtotalItem, 2, '.', ''),
                'tax_valor' => number_format($taxVal, 2, '.', ''),
            ];

            // Buyer info from Company
            $buyerDocType = '04'; // RUC
            $buyerId = preg_replace('/[^0-9a-zA-Z]/', '', $company->tax_id ?? '1799999999001');
            $buyerName = strtoupper($company->name ?? 'EMPRESA SUSCRIPTORA');
            $buyerEmail = $company->owner?->email ?? '';
            $paymentCode = '20'; // Transferencia / Otros con utilizacion de sistema financiero
        } else {
            // POS Sale Invoicing
            $buyer = $sale->partner;
            if ($buyer) {
                $buyerName = trim(strtoupper($buyer->business_name));
                $buyerId = preg_replace('/[^0-9a-zA-Z]/', '', $buyer->document_number);
                $buyerEmail = $buyer->email ?? '';
                
                if ($buyerName === 'CONSUMIDOR FINAL' || 
                    $buyerId === '9999999999999' || 
                    $buyerId === '9999999999' || 
                    $buyerId === '0999999999' || 
                    empty($buyerId)
                ) {
                    $buyerDocType = '07';
                    $buyerId = '9999999999999';
                    $buyerName = 'CONSUMIDOR FINAL';
                } else {
                    $docTypeRaw = strtoupper($buyer->document_type ?? '');
                    if ($docTypeRaw === 'RUC') {
                        $buyerDocType = '04';
                    } elseif ($docTypeRaw === 'CI' || $docTypeRaw === 'CEDULA') {
                        $buyerDocType = '05';
                    } elseif ($docTypeRaw === 'PASAPORTE') {
                        $buyerDocType = '06';
                    } else {
                        // Fallback default
                        $buyerDocType = '07';
                        $buyerId = '9999999999999';
                        $buyerName = 'CONSUMIDOR FINAL';
                    }
                }
            }

            $items = $sale->saleDetails ?? $sale->details ?? [];
            foreach ($items as $item) {
                $product = $item->product;
                $service = $item->service;
                $qty = (float)$item->quantity;
                $priceUnit = (float)($item->unit_price ?? $item->price ?? 0);
                
                $subtotalItem = round($qty * $priceUnit, 2);
                $totalSinImpuestos += $subtotalItem;

                $taxRate = 12.0;
                $taxCode = '2';
                $taxPercentageCode = '2';

                $taxModel = $item->tax ?? ($product ? $product->tax : ($service ? $service->tax : null));
                if ($taxModel) {
                    $taxRate = (float)$taxModel->rate;
                    if ($taxRate == 0) {
                        $taxPercentageCode = '0';
                    } elseif ($taxRate == 12) {
                        $taxPercentageCode = '2';
                    } elseif ($taxRate == 15) {
                        $taxPercentageCode = '4';
                    } else {
                        $taxPercentageCode = '2';
                    }
                }

                $taxVal = round($subtotalItem * ($taxRate / 100), 2);
                $totalFinal += ($subtotalItem + $taxVal);

                $key = $taxCode . '_' . $taxPercentageCode;
                if (!isset($impuestosMap[$key])) {
                    $impuestosMap[$key] = [
                        'codigo' => $taxCode,
                        'codigoPorcentaje' => $taxPercentageCode,
                        'tarifa' => number_format($taxRate, 2, '.', ''),
                        'baseImponible' => 0,
                        'valor' => 0
                    ];
                }
                $impuestosMap[$key]['baseImponible'] += $subtotalItem;
                $impuestosMap[$key]['valor'] += $taxVal;

                $code = 'SERV';
                $name = 'SERVICIO';
                if ($product) {
                    $code = $product->code;
                    $name = $product->name;
                } elseif ($service) {
                    $code = $service->code;
                    $name = $service->name;
                }

                $xmlDetails[] = [
                    'codigoPrincipal' => $code,
                    'descripcion' => substr(strtoupper($name), 0, 300),
                    'quantity' => $qty, // wait, let's keep consistency or map correct keys
                    'cantidad' => number_format($qty, 4, '.', ''),
                    'precioUnitario' => number_format($priceUnit, 4, '.', ''),
                    'descuento' => '0.0000',
                    'precioTotalSinImpuesto' => number_format($subtotalItem, 2, '.', ''),
                    'tax_codigo' => $taxCode,
                    'tax_codigoPorcentaje' => $taxPercentageCode,
                    'tax_tarifa' => number_format($taxRate, 2, '.', ''),
                    'tax_baseImponible' => number_format($subtotalItem, 2, '.', ''),
                    'tax_valor' => number_format($taxVal, 2, '.', ''),
                ];
            }

            $totalSinImpuestos = round($totalSinImpuestos, 2);
            $totalFinal = round($totalFinal, 2);

            if ($sale->payments && $sale->payments->count() > 0) {
                $firstPayment = $sale->payments->first();
                $methodName = strtoupper($firstPayment->paymentMethod?->name ?? '');
                if (str_contains($methodName, 'TARJETA') || str_contains($methodName, 'CREDITO') || str_contains($methodName, 'DEBITO') || str_contains($methodName, 'BANCO')) {
                    $paymentCode = '20';
                    $paymentName = 'TARJETA / TRANSFERENCIA';
                }
            }
        }

        // 4. Construct XML structure
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante" version="2.1.0"></factura>');
        
        // infoTributaria
        $infoTributaria = $xml->addChild('infoTributaria');
        $infoTributaria->addChild('ambiente', $envCode);
        $infoTributaria->addChild('tipoEmision', $emissionType);
        $infoTributaria->addChild('razonSocial', htmlspecialchars(strtoupper($company ? $company->name : 'FLEXDASH POS')));
        $infoTributaria->addChild('nombreComercial', htmlspecialchars(strtoupper($company ? ($company->name) : 'FLEXDASH')));
        $infoTributaria->addChild('ruc', $ruc);
        $infoTributaria->addChild('claveAcceso', $accessKey);
        $infoTributaria->addChild('codDoc', '01'); // Invoice
        $infoTributaria->addChild('estab', $establishment);
        $infoTributaria->addChild('ptoEmi', $emissionPoint);
        $infoTributaria->addChild('secuencial', $sequenceStr);
        $infoTributaria->addChild('dirMatriz', htmlspecialchars(strtoupper($company ? ($company->legal_address ?? $company->address ?? 'QUITO') : 'QUITO')));

        // infoFactura
        $infoFactura = $xml->addChild('infoFactura');
        $infoFactura->addChild('fechaEmision', $dateStr);
        $infoFactura->addChild('dirEstablecimiento', htmlspecialchars(strtoupper($company ? ($company->address ?? 'QUITO') : 'QUITO')));
        $infoFactura->addChild('obligadoContabilidad', 'NO');
        $infoFactura->addChild('tipoIdentificacionComprador', $buyerDocType);
        $infoFactura->addChild('razonSocialComprador', htmlspecialchars($buyerName));
        $infoFactura->addChild('identificacionComprador', $buyerId);
        $infoFactura->addChild('totalSinImpuestos', number_format($totalSinImpuestos, 2, '.', ''));
        $infoFactura->addChild('totalDescuento', '0.00');

        $totalConImpuestos = $infoFactura->addChild('totalConImpuestos');
        foreach ($impuestosMap as $imp) {
            $totalImpuesto = $totalConImpuestos->addChild('totalImpuesto');
            $totalImpuesto->addChild('codigo', $imp['codigo']);
            $totalImpuesto->addChild('codigoPorcentaje', $imp['codigoPorcentaje']);
            $totalImpuesto->addChild('baseImponible', number_format($imp['baseImponible'], 2, '.', ''));
            $totalImpuesto->addChild('valor', number_format($imp['valor'], 2, '.', ''));
        }

        $infoFactura->addChild('propina', '0.00');
        $infoFactura->addChild('importeTotal', number_format($totalFinal, 2, '.', ''));
        $infoFactura->addChild('moneda', 'DOLAR');

        $pagos = $infoFactura->addChild('pagos');
        $pago = $pagos->addChild('pago');
        $pago->addChild('formaPago', $paymentCode);
        $pago->addChild('total', number_format($totalFinal, 2, '.', ''));

        // detalles
        $detalles = $xml->addChild('detalles');
        foreach ($xmlDetails as $det) {
            $detalle = $detalles->addChild('detalle');
            $detalle->addChild('codigoPrincipal', htmlspecialchars($det['codigoPrincipal']));
            $detalle->addChild('descripcion', htmlspecialchars($det['descripcion']));
            $detalle->addChild('cantidad', $det['cantidad']);
            $detalle->addChild('precioUnitario', $det['precioUnitario']);
            $detalle->addChild('descuento', $det['descuento']);
            $detalle->addChild('precioTotalSinImpuesto', $det['precioTotalSinImpuesto']);

            $impuestos = $detalle->addChild('impuestos');
            $impuesto = $impuestos->addChild('impuesto');
            $impuesto->addChild('codigo', $det['tax_codigo']);
            $impuesto->addChild('codigoPorcentaje', $det['tax_codigoPorcentaje']);
            $impuesto->addChild('tarifa', $det['tax_tarifa']);
            $impuesto->addChild('baseImponible', $det['tax_baseImponible']);
            $impuesto->addChild('valor', $det['tax_valor']);
        }

        // infoAdicional (Optional: email)
        if ($buyerEmail) {
            $infoAdicional = $xml->addChild('infoAdicional');
            $campoAdicional = $infoAdicional->addChild('campoAdicional', htmlspecialchars($buyerEmail));
            $campoAdicional->addAttribute('nombre', 'Email');
        }

        return $xml->asXML();
    }

    /**
     * Compute the Modulo 11 check-digit for the access key.
     *
     * @param string $key First 48 digits of access key.
     * @return int
     */
    public function calculateModulo11(string $key): int
    {
        $sum = 0;
        $factor = 2;
        $len = strlen($key);

        for ($i = $len - 1; $i >= 0; $i--) {
            $sum += ((int)$key[$i]) * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;

        if ($checkDigit === 11) {
            return 0;
        }

        if ($checkDigit === 10) {
            return 1;
        }

        return $checkDigit;
    }
}
