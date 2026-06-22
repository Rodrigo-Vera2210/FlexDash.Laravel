<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Models\CompanyCertificate;
use App\Modules\Billing\Services\CertificateHelper;
use App\Modules\Billing\Services\ElectronicInvoicingService;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class SuperAdminBillingController extends Controller
{
    /**
     * Display the platform settings view.
     */
    public function index()
    {
        if (auth()->user()->role !== 'superadmin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        $config = BillingConfig::whereNull('company_id')->first();
        $certificates = CompanyCertificate::whereNull('company_id')->orderBy('is_default', 'desc')->get();

        return view('billing.superadmin.billing', compact('config', 'certificates'));
    }

    /**
     * Save/update the platform electronic billing settings.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'superadmin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        $config = BillingConfig::whereNull('company_id')->first();

        $rules = [
            'establishment' => 'required|string|size:3|regex:/^[0-9]+$/',
            'emission_point' => 'required|string|size:3|regex:/^[0-9]+$/',
            'last_sequence' => 'required|integer|min:0',
            'environment'   => 'required|in:pruebas,produccion',
        ];

        // If no config or uploading a new file
        if ($request->hasFile('certificate')) {
            $rules['certificate'] = 'required|file|max:2048'; // 2MB max
            $rules['password']    = 'required|string';
        }

        $request->validate($rules, [
            'establishment.size' => 'El código de establecimiento debe tener exactamente 3 dígitos.',
            'establishment.regex' => 'El código de establecimiento solo debe contener números.',
            'emission_point.size' => 'El punto de emisión debe tener exactamente 3 dígitos.',
            'emission_point.regex' => 'El punto de emisión solo debe contener números.',
            'last_sequence.min' => 'La secuencia inicial no puede ser menor a 0.',
        ]);

        try {
            DB::beginTransaction();

            $configData = [
                'establishment'  => $request->get('establishment'),
                'emission_point' => $request->get('emission_point'),
                'last_sequence'  => $request->get('last_sequence'),
                'environment'    => $request->get('environment'),
                'company_id'     => null, // Superadmin billing
            ];

            if ($config) {
                $config->update($configData);
            } else {
                $config = BillingConfig::create($configData);
            }

            if ($request->hasFile('certificate')) {
                $file = $request->file('certificate');
                $tempPath = $file->getRealPath();
                $password = $request->get('password');

                // Read and validate using CertificateHelper
                $fileContent = file_get_contents($tempPath);
                $certInfo = app(CertificateHelper::class)->extractMetadata($fileContent, $password);

                // Store securely
                $fileName = 'cert_platform_' . time() . '.p12';
                $securePath = 'secure_certificates/' . $fileName;
                Storage::put($securePath, $fileContent);

                // Create certificate record
                $isDefault = $request->boolean('is_default', false);
                CompanyCertificate::create([
                    'company_id' => null,
                    'certificate_path' => $securePath,
                    'certificate_password' => $password,
                    'certificate_expires_at' => $certInfo['expires_at'],
                    'owner_name' => $certInfo['owner_name'],
                    'ruc' => $certInfo['ruc'] ?? null,
                    'cedula' => $certInfo['cedula'] ?? null,
                    'is_default' => $isDefault,
                ]);
            }

            DB::commit();

            return redirect()->route('superadmin.billing.index')->with('success', 'Configuración de facturación del SuperAdmin guardada correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Set a platform certificate as default.
     */
    public function setDefault(CompanyCertificate $certificate)
    {
        if (auth()->user()->role !== 'superadmin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        if ($certificate->company_id !== null) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $certificate->update(['is_default' => true]);

        return redirect()->route('superadmin.billing.index')->with('success', 'Certificado de la plataforma establecido como predeterminado.');
    }

    /**
     * Delete a platform certificate.
     */
    public function destroyCertificate(CompanyCertificate $certificate)
    {
        if (auth()->user()->role !== 'superadmin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        if ($certificate->company_id !== null) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        // Delete from storage
        if ($certificate->certificate_path) {
            Storage::delete($certificate->certificate_path);
        }

        $certificate->delete();

        return redirect()->route('superadmin.billing.index')->with('success', 'Certificado de la plataforma eliminado correctamente.');
    }

    /**
     * Manually trigger billing for a subscription payment.
     */
    public function invoicePayment(SubscriptionPayment $payment, ElectronicInvoicingService $invoicingService)
    {
        if (auth()->user()->role !== 'superadmin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
        }

        if ($payment->status !== 'approved') {
            return redirect()->back()->with('error', 'Solo se pueden facturar pagos de suscripción aprobados.');
        }

        try {
            $invoicingService->process($payment);
            return redirect()->back()->with('success', 'Factura electrónica para suscripción emitida y autorizada correctamente.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al emitir factura: ' . $e->getMessage());
        }
    }
}
