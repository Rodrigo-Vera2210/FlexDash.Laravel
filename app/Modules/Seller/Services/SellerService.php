<?php

namespace App\Modules\Seller\Services;

use App\Models\User;
use App\Modules\Registration\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SellerService
{
    /**
     * Check if the company has reached its seller limit.
     */
    public function checkLimitReached(Company $company): bool
    {
        $plan = $company->subscription_plan;
        
        $activeSellersCount = User::where('company_id', $company->id)
            ->where('role', 'vendedor')
            ->where('status', 'active')
            ->count();

        if ($plan === 'basic' && $activeSellersCount >= 2) {
            return true;
        }

        if ($plan === 'standard' && $activeSellersCount >= 10) {
            return true;
        }

        return false;
    }

    /**
     * Create a new seller account.
     */
    public function createSeller(array $data, Company $company): User
    {
        if ($this->checkLimitReached($company)) {
            throw ValidationException::withMessages([
                'limit' => 'El límite de vendedores para su plan de suscripción ha sido alcanzado.',
            ]);
        }

        return User::create([
            'company_id'        => $company->id,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'role'              => 'vendedor',
            'status'            => 'active',
            'email_verified_at' => now(), // bypass OTP verification
        ]);
    }
}
