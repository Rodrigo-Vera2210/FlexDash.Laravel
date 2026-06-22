<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\BillingConfig;
use App\Modules\Billing\Models\CompanyCertificate;
use App\Modules\Billing\Services\CertificateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class BillingSettingsController extends Controller
{
    /**
     * Display the billing settings view.
     */
    public function index()
    {
        $company = auth()->user()->company;

        if (!$company || !$company->has_electronic_billing) {
            return redirect()->route('dashboard')->with('error', 'Su plan de suscripción no incluye acceso al módulo de Facturación Electrónica.');
        }

        $config = BillingConfig::where('company_id', $company->id)->first();
        $certificates = $company->companyCertificates()->orderBy('is_default', 'desc')->get();

        return view('billing.settings.config', compact('config', 'certificates'));
    }

    /**
     * Save/update the electronic billing settings and upload new certificates.
     */
    public function store(Request $request)
    {
        $company = auth()->user()->company;

        if (!$company || !$company->has_electronic_billing) {
            return redirect()->route('dashboard')->with('error', 'Su plan de suscripción no incluye acceso al módulo de Facturación Electrónica.');
        }

        $config = BillingConfig::where('company_id', $company->id)->first();

        $rules = [
            'establishment' => 'required|string|size:3|regex:/^[0-9]+$/',
            'emission_point' => 'required|string|size:3|regex:/^[0-9]+$/',
            'last_sequence' => 'required|integer|min:0',
            'environment'   => 'required|in:pruebas,produccion',
        ];

        // Only require certificate if no existing config or explicitly uploading one
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
                'company_id'     => $company->id,
            ];

            if ($config) {
                $config->update($configData);
            } else {
                $config = BillingConfig::create($configData);
            }

            if ($request->hasFile('certificate')) {
                // Enforce plan limits
                if (!$company->canUploadCertificate()) {
                    throw new Exception("Límite superado. Su plan permite un máximo de {$company->max_certificates} certificado(s).");
                }

                $file = $request->file('certificate');
                $tempPath = $file->getRealPath();
                $password = $request->get('password');

                // Read and validate using CertificateHelper
                $fileContent = file_get_contents($tempPath);
                $certInfo = app(CertificateHelper::class)->extractMetadata($fileContent, $password);

                // Validate RUC/CI against Company Tax ID (RUC)
                $companyTaxId = preg_replace('/[^0-9]/', '', $company->tax_id ?? '');
                if (!empty($companyTaxId)) {
                    $certRuc = $certInfo['ruc'] ?? null;
                    $certCedula = $certInfo['cedula'] ?? null;
                    $matched = false;

                    if ($certRuc && $certRuc === $companyTaxId) {
                        $matched = true;
                    } elseif ($certCedula && str_starts_with($companyTaxId, $certCedula)) {
                        $matched = true;
                    }

                    if (!$matched) {
                        $certDoc = $certRuc ?: ($certCedula ?: 'no identificado');
                        throw new Exception("El RUC/Cédula del certificado ($certDoc) no coincide con el RUC de la empresa ($companyTaxId).");
                    }
                }

                // Store securely
                $fileName = 'cert_' . $company->id . '_' . time() . '.p12';
                $securePath = 'secure_certificates/' . $fileName;
                Storage::put($securePath, $fileContent);

                // Create certificate record
                $isDefault = $request->boolean('is_default', false);
                CompanyCertificate::create([
                    'company_id' => $company->id,
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

            return redirect()->route('billing.settings.index')->with('success', 'Configuración guardada correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Set a certificate as default.
     */
    public function setDefault(CompanyCertificate $certificate)
    {
        $company = auth()->user()->company;

        if ($certificate->company_id !== $company->id) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $certificate->update(['is_default' => true]);

        return redirect()->route('billing.settings.index')->with('success', 'Certificado establecido como predeterminado.');
    }

    /**
     * Delete a certificate.
     */
    public function destroyCertificate(CompanyCertificate $certificate)
    {
        $company = auth()->user()->company;

        if ($certificate->company_id !== $company->id) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        // Delete from storage
        if ($certificate->certificate_path) {
            Storage::delete($certificate->certificate_path);
        }

        $certificate->delete();

        return redirect()->route('billing.settings.index')->with('success', 'Certificado eliminado correctamente.');
    }
}
