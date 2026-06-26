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

    public function showCompany(Company $company)
    {
        $company->load(['users', 'subscriptionPayments' => function ($query) {
            $query->latest();
        }]);

        // Filter active administrators (owner and company_representative roles)
        $activeAdmins = $company->users->filter(function ($user) {
            return in_array($user->role, ['owner', 'company_representative']) && $user->status === 'active';
        });

        // Filter active sellers (vendedor role)
        $activeSellers = $company->users->filter(function ($user) {
            return $user->role === 'vendedor' && $user->status === 'active';
        });

        // Subscription start date is either the first approved signup payment or company creation date
        $firstApprovedPayment = $company->subscriptionPayments
            ->where('status', 'approved')
            ->where('type', 'signup')
            ->last(); // oldest signup payment

        $startDate = $firstApprovedPayment ? $firstApprovedPayment->created_at : $company->created_at;

        return view('superadmin.company-detail', compact(
            'company',
            'activeAdmins',
            'activeSellers',
            'startDate'
        ));
    }

    public function approveCompany(Company $company, Request $request)
    {
        $request->validate([
            'payment_id' => ['required', 'integer', 'exists:subscription_payments,id'],
        ]);

        $this->superAdminService->approveCompany($company, $request->payment_id);

        return redirect()->back()
            ->with('success', "Empresa '{$company->name}' aprobada y activada con éxito.");
    }

    public function rejectCompany(Company $company, Request $request)
    {
        $request->validate([
            'payment_id' => ['required', 'integer', 'exists:subscription_payments,id'],
            'reason'     => ['nullable', 'string', 'max:255'],
        ]);

        $this->superAdminService->rejectCompany($company, $request->payment_id, $request->reason);

        return redirect()->back()
            ->with('success', "Pago para la empresa '{$company->name}' rechazado.");
    }

    public function toggleStatus(Company $company, Request $request)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->superAdminService->toggleSubscription($company, $request->reason);
            return redirect()->back()
                ->with('success', "El estado de suscripción para '{$company->name}' ha sido actualizado.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
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

    public function paymentsIndex(Request $request)
    {
        $status = $request->query('status');
        $plan = $request->query('plan');

        $query = \App\Models\SubscriptionPayment::with(['company', 'electronicInvoice'])->latest();

        if ($status) {
            $query->where('status', $status);
        }
        if ($plan) {
            $query->where('plan', $plan);
        }

        $payments = $query->paginate(15)->withQueryString();

        // Calculate statistics based on all payments
        $allPayments = \App\Models\SubscriptionPayment::all();
        
        $totalApproved = $allPayments->where('status', 'approved')->count();
        $totalPending = $allPayments->where('status', 'pending')->count();
        $totalRejected = $allPayments->where('status', 'rejected')->count();

        // Estimate revenue
        $estimatedRevenue = $allPayments->where('status', 'approved')->reduce(function ($carry, $payment) {
            $price = $payment->plan === 'standard' ? 59 : ($payment->plan === 'premium' ? 99 : 29);
            return $carry + $price;
        }, 0);

        return view('superadmin.payments', compact(
            'payments',
            'totalApproved',
            'totalPending',
            'totalRejected',
            'estimatedRevenue',
            'status',
            'plan'
        ));
    }

    public function plansIndex()
    {
        $plans = $this->superAdminService->getAllPlans();
        return view('superadmin.plans.index', compact('plans'));
    }

    public function plansCreate()
    {
        return view('superadmin.plans.edit', ['plan' => null]);
    }

    public function plansStore(Request $request)
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'code'                     => ['required', 'string', 'max:255', 'unique:plans,code'],
            'price'                    => ['required', 'numeric', 'min:0'],
            'max_admins'               => ['required', 'integer', 'min:1'],
            'max_sellers'              => ['required', 'integer', 'min:0'],
            'max_monthly_transactions' => ['required', 'integer', 'min:1'],
            'max_branches'             => ['required', 'integer', 'min:1'],
            'monthly_invoice_limit'    => ['required', 'integer', 'min:0'],
            'modules'                  => ['nullable', 'array'],
            'is_active'                => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active') ? true : false;

        $this->superAdminService->storePlan($data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan '{$data['name']}' creado con éxito.");
    }

    public function plansEdit(int $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function plansUpdate(Request $request, int $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'code'                     => ['required', 'string', 'max:255', 'unique:plans,code,' . $plan->id],
            'price'                    => ['required', 'numeric', 'min:0'],
            'max_admins'               => ['required', 'integer', 'min:1'],
            'max_sellers'              => ['required', 'integer', 'min:0'],
            'max_monthly_transactions' => ['required', 'integer', 'min:1'],
            'max_branches'             => ['required', 'integer', 'min:1'],
            'monthly_invoice_limit'    => ['required', 'integer', 'min:0'],
            'modules'                  => ['nullable', 'array'],
            'is_active'                => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active') ? true : false;

        $this->superAdminService->updatePlanDetails($plan, $data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan '{$data['name']}' actualizado con éxito.");
    }

    public function plansDestroy(int $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        $name = $plan->name;
        
        $this->superAdminService->deletePlan($plan);

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan '{$name}' eliminado con éxito.");
    }

    public function updateCustomLimits(Company $company, Request $request)
    {
        $data = $request->validate([
            'override_modules'         => ['nullable'],
            'active_modules'           => ['nullable', 'array'],
            'override_transactions'    => ['nullable'],
            'max_monthly_transactions' => ['nullable', 'integer', 'min:1'],
            'override_admins'          => ['nullable'],
            'max_admins'               => ['nullable', 'integer', 'min:1'],
            'override_sellers'         => ['nullable'],
            'max_sellers'              => ['nullable', 'integer', 'min:0'],
        ]);

        $this->superAdminService->updateCompanyCustomLimits($company, $data);

        return redirect()->back()
            ->with('success', "Configuraciones personalizadas de suscripción para '{$company->name}' actualizadas.");
    }
}
