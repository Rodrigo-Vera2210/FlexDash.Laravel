<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Models\ElectronicInvoice;
use App\Modules\Billing\Models\CompanyCertificate;
use App\Modules\Billing\Emails\ElectronicInvoiceMail;
use App\Modules\Registration\Models\Company;
use App\Modules\Sale\Models\Sale;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ElectronicInvoicingService
{
    public function __construct(
        protected XmlGeneratorService $xmlGenerator,
        protected XmlSignerService $xmlSigner,
        protected SriSoapClientService $sriSoapClient,
        protected RideGeneratorService $rideGenerator
    ) {}

    /**
     * Process electronic invoice for a given sale or subscription payment.
     *
     * @param object $model The Sale or SubscriptionPayment model instance.
     * @param int|null $certificateId Optional explicit certificate ID to use.
     * @return ElectronicInvoice
     * @throws Exception
     */
    public function process(object $model, ?int $certificateId = null): ElectronicInvoice
    {
        // 1. Determine company context and validation rules
        $company = null;
        $isPlatformBilling = false;

        if ($model instanceof Sale) {
            $company = $model->company;
            if (!$company) {
                throw new Exception("La venta no tiene una empresa asociada.");
            }

            // Check if subscription has billing enabled
            if (!$company->has_electronic_billing) {
                throw new Exception("El plan de suscripción de la empresa no incluye acceso al módulo de Facturación Electrónica.");
            }

            // Check monthly limits
            $monthlyLimit = $company->monthly_invoice_limit;
            if ($monthlyLimit > 0) {
                $startOfMonth = now()->startOfMonth();
                $endOfMonth = now()->endOfMonth();
                $authorizedCount = ElectronicInvoice::where('company_id', $company->id)
                    ->where('status', 'authorized')
                    ->whereBetween('authorized_at', [$startOfMonth, $endOfMonth])
                    ->count();

                if ($authorizedCount >= $monthlyLimit) {
                    throw new Exception("Se ha alcanzado el límite mensual de facturación electrónica (" . $monthlyLimit . " facturas).");
                }
            }
        } elseif ($model instanceof SubscriptionPayment) {
            $isPlatformBilling = true;
            $company = $model->company;
            if (!$company) {
                throw new Exception("El pago de suscripción no tiene una empresa asociada.");
            }
        } else {
            throw new Exception("Modelo no soportado para facturación electrónica.");
        }

        // 2. Fetch billing config
        if ($isPlatformBilling) {
            $config = BillingConfig::whereNull('company_id')->where('is_active', true)->first();
            if (!$config) {
                throw new Exception("La configuración de facturación de la plataforma (SuperAdmin) no está configurada o no está activa.");
            }
        } else {
            $config = BillingConfig::where('company_id', $company->id)->where('is_active', true)->first();
            if (!$config) {
                throw new Exception("La configuración de facturación de la empresa no está configurada o no está activa.");
            }
        }

        // 2.1 Resolve Digital Certificate
        if ($isPlatformBilling) {
            $certificate = CompanyCertificate::whereNull('company_id')->where('is_default', true)->first();
            if (!$certificate) {
                throw new Exception("El certificado de firma electrónica de la plataforma (SuperAdmin) no está configurado.");
            }
        } else {
            if ($certificateId) {
                $certificate = CompanyCertificate::where('company_id', $company->id)->find($certificateId);
                if (!$certificate) {
                    throw new Exception("El certificado de firma electrónica seleccionado no pertenece a la empresa.");
                }
            } else {
                $certificate = CompanyCertificate::where('company_id', $company->id)->where('is_default', true)->first();
                if (!$certificate) {
                    throw new Exception("La empresa no tiene un certificado de firma electrónica configurado por defecto.");
                }
            }
        }

        // Check if certificate is expired
        if ($certificate->certificate_expires_at && Carbon::parse($certificate->certificate_expires_at)->isPast()) {
            throw new Exception("El certificado de firma electrónica ha expirado.");
        }

        return DB::transaction(function () use ($model, $config, $company, $isPlatformBilling, $certificate) {
            // Check if an invoice already exists for this model
            $existingInvoice = ElectronicInvoice::where('invoicable_type', get_class($model))
                ->where('invoicable_id', $model->id)
                ->first();

            if ($existingInvoice) {
                if ($existingInvoice->status === 'authorized') {
                    return $existingInvoice;
                }
                $invoice = $existingInvoice;
            } else {
                $invoice = new ElectronicInvoice();
                $invoice->invoicable_type = get_class($model);
                $invoice->invoicable_id = $model->id;
                if ($isPlatformBilling) {
                    $invoice->company_id = null; // Issued by platform
                } else {
                    $invoice->company_id = $company->id;
                }
            }

            // Link the specific certificate used
            $invoice->certificate_id = $certificate->id;

            // Increment sequence
            $sequenceNumber = $config->last_sequence + 1;
            $config->increment('last_sequence');

            // Format sequence string
            $establishment = str_pad($config->establishment, 3, '0', STR_PAD_LEFT);
            $emissionPoint = str_pad($config->emission_point, 3, '0', STR_PAD_LEFT);
            $sequenceStr = $establishment . '-' . $emissionPoint . '-' . str_pad($sequenceNumber, 9, '0', STR_PAD_LEFT);

            // Compute access key
            $date = Carbon::parse($model->issue_date ?? $model->created_at ?? now());
            $dateAccessKey = $date->format('dmY');
            $ruc = $isPlatformBilling ? '9999999999999' : ($company->tax_id ?? '9999999999999');
            if (strlen($ruc) !== 13) {
                $ruc = str_pad($ruc, 13, '0', STR_PAD_RIGHT);
            }
            $envCode = $config->environment === 'produccion' ? '2' : '1';
            $sequenceStrPad = str_pad($sequenceNumber, 9, '0', STR_PAD_LEFT);
            $numericCode = '12345678';
            $emissionType = '1';

            $accessKeyWithoutCheckDigit = $dateAccessKey . '01' . $ruc . $envCode . $establishment . $emissionPoint . $sequenceStrPad . $numericCode . $emissionType;
            
            // Calculate modulo 11
            $sum = 0;
            $factor = 2;
            $len = strlen($accessKeyWithoutCheckDigit);
            for ($i = $len - 1; $i >= 0; $i--) {
                $sum += ((int)$accessKeyWithoutCheckDigit[$i]) * $factor;
                $factor = $factor === 7 ? 2 : $factor + 1;
            }
            $remainder = $sum % 11;
            $checkDigit = 11 - $remainder;
            if ($checkDigit === 11) {
                $checkDigit = 0;
            } elseif ($checkDigit === 10) {
                $checkDigit = 1;
            }
            $accessKey = $accessKeyWithoutCheckDigit . $checkDigit;

            $invoice->sequence = $sequenceStr;
            $invoice->access_key = $accessKey;
            $invoice->status = 'draft';
            $invoice->save();

            try {
                // Generate XML
                $xmlContent = $this->xmlGenerator->generateInvoiceXml($model, $config, $sequenceNumber);
                
                // Sign XML
                if (!$certificate->certificate_path || !Storage::exists($certificate->certificate_path)) {
                    throw new Exception("El archivo de certificado no se encuentra en el almacenamiento.");
                }
                $p12Content = Storage::get($certificate->certificate_path);
                $password = $certificate->decrypted_password;

                $signedXml = $this->xmlSigner->signXml($xmlContent, $p12Content, $password);

                $xmlFileName = 'invoice_' . $invoice->id . '_' . time() . '.xml';
                $xmlPath = 'secure_invoices/' . $xmlFileName;
                Storage::put($xmlPath, $signedXml);
                
                $invoice->xml_path = $xmlPath;
                $invoice->status = 'signed';
                $invoice->save();
                
                Log::info($invoice);
                // $this->info($invoice);

                // 3. SOAP Reception
                $reception = $this->sriSoapClient->sendToReception($signedXml, $config->environment);
                if ($reception['status'] !== 'RECIBIDA') {
                    $errors = !empty($reception['errors']) ? implode('; ', $reception['errors']) : 'Error desconocido de recepción SRI.';
                    throw new Exception("Rechazado por recepción SRI: " . $errors);
                }

                $invoice->status = 'received';
                $invoice->save();

                // 4. SOAP Authorization
                if (!app()->environment('testing')) {
                    sleep(2);
                }
                
                $authorization = $this->sriSoapClient->queryAuthorization($accessKey, $config->environment);
                if ($authorization['status'] !== 'AUTORIZADO') {
                    $errors = !empty($authorization['errors']) ? implode('; ', $authorization['errors']) : 'Error desconocido de autorización SRI.';
                    throw new Exception("Rechazado por autorización SRI: " . $errors);
                }

                $invoice->status = 'authorized';
                $invoice->authorized_at = isset($authorization['date']) ? Carbon::parse($authorization['date']) : now();
                
                // Save authorized XML
                if (!empty($authorization['xml'])) {
                    Storage::put($xmlPath, $authorization['xml']);
                }
                $invoice->save();

                // 5. Generate RIDE PDF
                $pdfPath = $this->rideGenerator->generateRidePdf($invoice);
                $invoice->pdf_path = $pdfPath;
                $invoice->save();

                // 6. Notify Client via Email
                $recipientEmail = '';
                if ($model instanceof Sale) {
                    $recipientEmail = $model->partner?->email ?? '';
                } elseif ($model instanceof SubscriptionPayment) {
                    $recipientEmail = $company->owner?->email ?? '';
                }

                if ($recipientEmail) {
                    try {
                        Mail::to($recipientEmail)->send(new ElectronicInvoiceMail($invoice));
                    } catch (Exception $mailEx) {
                        Log::error("Error al enviar correo de factura electrónica: " . $mailEx->getMessage());
                    }
                }

            } catch (Exception $e) {
                $invoice->status = 'failed';
                $invoice->sri_error_details = $e->getMessage();
                $invoice->save();
                throw $e;
            }

            return $invoice;
        });
    }
}
