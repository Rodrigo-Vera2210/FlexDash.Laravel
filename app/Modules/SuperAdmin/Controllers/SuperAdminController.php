<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Registration\Models\Company;
use App\Modules\SuperAdmin\Services\SuperAdminService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function __construct(
        protected SuperAdminService $superAdminService
    ) {}

    public function dashboard()
    {
        $metrics = $this->superAdminService->getDashboardMetrics();
        $companies = $this->superAdminService->getCompaniesList();
        $pendingPayments = $this->superAdminService->getPendingPayments();

        return view('superadmin.dashboard', compact('metrics', 'companies', 'pendingPayments'));
    }

    public function approveCompany(Company $company, Request $request)
    {
        $request->validate([
            'payment_id' => ['required', 'integer', 'exists:subscription_payments,id'],
        ]);

        $this->superAdminService->approveCompany($company, $request->payment_id);

        return redirect()->route('superadmin.dashboard')
            ->with('success', "Empresa '{$company->name}' aprobada y activada con éxito.");
    }

    public function rejectCompany(Company $company, Request $request)
    {
        $request->validate([
            'payment_id' => ['required', 'integer', 'exists:subscription_payments,id'],
        ]);

        $this->superAdminService->rejectCompany($company, $request->payment_id);

        return redirect()->route('superadmin.dashboard')
            ->with('success', "Pago para la empresa '{$company->name}' rechazado.");
    }

    public function toggleStatus(Company $company)
    {
        $this->superAdminService->toggleSubscription($company);

        return redirect()->route('superadmin.dashboard')
            ->with('success', "El estado de suscripción para '{$company->name}' ha sido actualizado.");
    }

    public function changePlan(Company $company, Request $request)
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:basic,standard'],
        ]);

        $this->superAdminService->updatePlan($company, $request->plan);

        return redirect()->route('superadmin.dashboard')
            ->with('success', "Plan de '{$company->name}' actualizado a " . ucfirst($request->plan) . ".");
    }
}
