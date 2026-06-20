<?php

namespace App\Modules\SuperAdmin\Services;

use App\Models\User;
use App\Models\SubscriptionPayment;
use App\Modules\Registration\Models\Company;
use Illuminate\Support\Facades\DB;

class SuperAdminService
{
    public function getDashboardMetrics(): array
    {
        return [
            'total'   => Company::count(),
            'active'  => Company::where('subscription_status', 'active')->count(),
            'pending' => Company::where('subscription_status', 'pending_approval')->count(),
            'blocked' => Company::whereIn('subscription_status', ['inactive', 'suspended', 'rejected'])->count(),
        ];
    }

    public function getCompaniesList()
    {
        return Company::withCount([
            'users as admins_count' => function ($query) {
                $query->whereIn('role', ['owner', 'company_representative']);
            },
            'users as sellers_count' => function ($query) {
                $query->where('role', 'vendedor');
            }
        ])->get();
    }

    public function getPendingPayments()
    {
        return SubscriptionPayment::with('company')
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function toggleSubscription(Company $company): Company
    {
        $company->subscription_status = $company->subscription_status === 'active' ? 'inactive' : 'active';
        $company->save();

        return $company;
    }

    public function updatePlan(Company $company, string $plan): Company
    {
        $company->subscription_plan = $plan;
        $company->save();

        return $company;
    }

    public function approveCompany(Company $company, int $paymentId): Company
    {
        DB::transaction(function () use ($company, $paymentId) {
            $payment = SubscriptionPayment::findOrFail($paymentId);
            $payment->status = 'approved';
            $payment->save();

            $company->subscription_plan = $payment->plan;
            $company->subscription_status = 'active';
            
            // Set expiration date to 1 month in the future
            $company->subscription_expires_at = now()->addMonth();
            $company->save();

            // Activate administrator user accounts for the company
            User::where('company_id', $company->id)
                ->where('status', 'pending_activation')
                ->update(['status' => 'active']);
        });

        return $company->fresh();
    }

    public function rejectCompany(Company $company, int $paymentId): Company
    {
        DB::transaction(function () use ($company, $paymentId) {
            $payment = SubscriptionPayment::findOrFail($paymentId);
            $payment->status = 'rejected';
            $payment->save();

            $company->subscription_status = 'rejected';
            $company->save();
        });

        return $company->fresh();
    }
}
