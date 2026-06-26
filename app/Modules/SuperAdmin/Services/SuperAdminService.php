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

    public function toggleSubscription(Company $company, ?string $reason = null): Company
    {
        $oldStatus = $company->subscription_status;
        $oldReason = $company->suspension_reason;

        if ($company->subscription_status !== 'active') {
            $hasApproved = $company->subscriptionPayments()->where('status', 'approved')->exists();
            if (!$hasApproved) {
                throw new \Exception("No se puede activar la suscripción de la empresa sin verificar y aprobar al menos un pago primero.");
            }
            $company->subscription_status = 'active';
            $company->suspension_reason = null; // Clear reason on activation
        } else {
            $company->subscription_status = 'inactive';
            $company->suspension_reason = $reason ?: 'Desactivado por el superadministrador';
        }
        $company->save();

        // Audit Log
        \App\Modules\Audit\Models\AuditLog::record(
            'subscription.toggle_status',
            $company,
            ['subscription_status' => $oldStatus, 'suspension_reason' => $oldReason],
            ['subscription_status' => $company->subscription_status, 'suspension_reason' => $company->suspension_reason]
        );

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
            $oldPaymentStatus = $payment->status;
            
            $payment->status = 'approved';
            $payment->rejection_reason = null; // clear rejection reason
            $payment->save();

            $oldCompanyStatus = $company->subscription_status;
            $oldPlan = $company->subscription_plan;
            $oldExpires = $company->subscription_expires_at;
            $oldReason = $company->suspension_reason;

            $company->subscription_plan = $payment->plan;
            $company->subscription_status = 'active';
            $company->suspension_reason = null; // clear suspension reason
            
            // Extend subscription by selected payment duration
            $company->subscription_expires_at = now()->addMonths($payment->duration_months ?? 1);

            $plan = \App\Models\Plan::where('code', $payment->plan)->first();
            if ($plan) {
                $company->max_branches = $plan->max_branches;
            }

            $company->save();

            // Create default branch if none exists
            if ($company->branches()->count() === 0) {
                $branch = \App\Modules\Branch\Models\Branch::create([
                    'company_id'          => $company->id,
                    'name'                => 'Matriz',
                    'establishment_code'  => '001',
                    'is_active'           => true,
                ]);

                User::where('company_id', $company->id)
                    ->whereIn('role', ['owner', 'company_representative'])
                    ->update(['branch_id' => $branch->id]);
            }

            // Activate administrator user accounts for the company
            User::where('company_id', $company->id)
                ->where('status', 'pending_activation')
                ->update(['status' => 'active']);

            // Notify company owner of approved payment
            $company->owner?->notify(new \App\Modules\SuperAdmin\Notifications\PaymentApprovedNotification($payment));

            // Audit payment approval
            \App\Modules\Audit\Models\AuditLog::record(
                'subscription.approve_payment',
                $payment,
                ['status' => $oldPaymentStatus],
                ['status' => 'approved']
            );

            // Trigger platform billing for subscription automatically
            try {
                $invoicingService = app(\App\Modules\Billing\Services\ElectronicInvoicingService::class);
                $invoicingService->process($payment);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("No se pudo auto-generar la factura de suscripción para el pago #{$payment->id}: " . $e->getMessage());
                if (app()->environment('testing')) {
                    throw $e;
                }
            }

            // Audit company activation
            \App\Modules\Audit\Models\AuditLog::record(
                'subscription.activate_company',
                $company,
                [
                    'subscription_status' => $oldCompanyStatus,
                    'subscription_plan' => $oldPlan,
                    'subscription_expires_at' => $oldExpires ? $oldExpires->toDateTimeString() : null,
                    'suspension_reason' => $oldReason
                ],
                [
                    'subscription_status' => 'active',
                    'subscription_plan' => $payment->plan,
                    'subscription_expires_at' => $company->subscription_expires_at ? $company->subscription_expires_at->toDateTimeString() : null,
                    'suspension_reason' => null
                ]
            );
        });

        return $company->fresh();
    }

    public function rejectCompany(Company $company, int $paymentId, ?string $reason = null): Company
    {
        DB::transaction(function () use ($company, $paymentId, $reason) {
            $payment = SubscriptionPayment::findOrFail($paymentId);
            $oldPaymentStatus = $payment->status;
            $oldRejectionReason = $payment->rejection_reason;

            $payment->status = 'rejected';
            $payment->rejection_reason = $reason ?: 'Pago rechazado por el superadministrador';
            $payment->save();

            $oldCompanyStatus = $company->subscription_status;
            $oldReason = $company->suspension_reason;

            $company->subscription_status = 'rejected';
            $company->suspension_reason = $payment->rejection_reason; // Copy to company level for display
            $company->save();

            // Notify company owner of rejected payment
            $company->owner?->notify(new \App\Modules\SuperAdmin\Notifications\PaymentRejectedNotification($payment, $payment->rejection_reason));

            // Audit payment rejection
            \App\Modules\Audit\Models\AuditLog::record(
                'subscription.reject_payment',
                $payment,
                ['status' => $oldPaymentStatus, 'rejection_reason' => $oldRejectionReason],
                ['status' => 'rejected', 'rejection_reason' => $payment->rejection_reason]
            );

            // Audit company rejection
            \App\Modules\Audit\Models\AuditLog::record(
                'subscription.reject_company',
                $company,
                ['subscription_status' => $oldCompanyStatus, 'suspension_reason' => $oldReason],
                ['subscription_status' => 'rejected', 'suspension_reason' => $company->suspension_reason]
            );
        });

        return $company->fresh();
    }

    public function getAllPlans()
    {
        return \App\Models\Plan::all();
    }

    public function storePlan(array $data): \App\Models\Plan
    {
        return \App\Models\Plan::create([
            'name'                     => $data['name'],
            'code'                     => $data['code'],
            'price'                    => $data['price'],
            'max_admins'               => $data['max_admins'],
            'max_sellers'              => $data['max_sellers'],
            'max_monthly_transactions' => $data['max_monthly_transactions'],
            'max_branches'             => $data['max_branches'],
            'monthly_invoice_limit'    => $data['monthly_invoice_limit'],
            'modules'                  => $data['modules'] ?? [],
            'is_active'                => $data['is_active'] ?? true,
        ]);
    }

    public function updatePlanDetails(\App\Models\Plan $plan, array $data): \App\Models\Plan
    {
        $plan->update([
            'name'                     => $data['name'],
            'code'                     => $data['code'],
            'price'                    => $data['price'],
            'max_admins'               => $data['max_admins'],
            'max_sellers'              => $data['max_sellers'],
            'max_monthly_transactions' => $data['max_monthly_transactions'],
            'max_branches'             => $data['max_branches'],
            'monthly_invoice_limit'    => $data['monthly_invoice_limit'],
            'modules'                  => $data['modules'] ?? [],
            'is_active'                => $data['is_active'] ?? true,
        ]);

        return $plan;
    }

    public function deletePlan(\App\Models\Plan $plan): void
    {
        $plan->delete();
    }

    public function updateCompanyCustomLimits(Company $company, array $data): Company
    {
        $oldModules = $company->active_modules;
        $oldLimit = $company->max_monthly_transactions;
        $oldAdmins = $company->max_admins;
        $oldSellers = $company->max_sellers;

        // Save overrides. If they are empty or equal to defaults we can save them as null or keep them as overrides.
        // Let's check: if active_modules is checked, they are overridden. If 'override_modules' is unchecked, set it to null.
        $company->active_modules = isset($data['override_modules']) ? ($data['active_modules'] ?? []) : null;
        $company->max_monthly_transactions = isset($data['override_transactions']) && $data['max_monthly_transactions'] !== '' ? (int)$data['max_monthly_transactions'] : null;
        $company->max_admins = isset($data['override_admins']) && $data['max_admins'] !== '' ? (int)$data['max_admins'] : null;
        $company->max_sellers = isset($data['override_sellers']) && $data['max_sellers'] !== '' ? (int)$data['max_sellers'] : null;
        $company->save();

        // Audit Log
        \App\Modules\Audit\Models\AuditLog::record(
            'subscription.update_custom_limits',
            $company,
            [
                'active_modules' => $oldModules,
                'max_monthly_transactions' => $oldLimit,
                'max_admins' => $oldAdmins,
                'max_sellers' => $oldSellers,
            ],
            [
                'active_modules' => $company->active_modules,
                'max_monthly_transactions' => $company->max_monthly_transactions,
                'max_admins' => $company->max_admins,
                'max_sellers' => $company->max_sellers,
            ]
        );

        return $company;
    }
}
