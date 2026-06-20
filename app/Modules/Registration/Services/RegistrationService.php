<?php

namespace App\Modules\Registration\Services;

use App\Models\User;
use App\Modules\Registration\Contracts\EmailVerificationServiceInterface;
use App\Modules\Registration\Contracts\RegistrationServiceInterface;
use App\Modules\Registration\Repositories\CompanyRepository;
use App\Modules\Registration\Repositories\EmailVerificationRepository;
use App\Modules\Registration\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the multi-step registration workflow.
 *
 * Responsible for creating Company + User + EmailVerification records inside
 * a single database transaction. Business logic lives exclusively here; no
 * direct DB queries are issued — all persistence is delegated to repositories.
 *
 * Constitution rule: Application Layer — business logic here, not in
 * controllers or models. Max method length: 30 lines. SOLID: constructor
 * injection only.
 */
class RegistrationService implements RegistrationServiceInterface
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected UserRepository $userRepository,
        protected EmailVerificationRepository $emailVerificationRepository,
        protected EmailVerificationServiceInterface $emailVerificationService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createPendingRegistration(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $company = $this->companyRepository->create(
                $this->buildCompanyData($data)
            );

            $user = $this->userRepository->create(
                $this->buildUserData($data, $company->id)
            );

            // Create subscription payment log
            \App\Models\SubscriptionPayment::create([
                'company_id'          => $company->id,
                'plan'                => $data['subscription_plan'] ?? 'basic',
                'bank_origin'         => $data['bank_origin'] ?? '',
                'account_destination' => $data['account_destination'] ?? '',
                'receipt_path'        => $data['payment_receipt_path'] ?? '',
                'status'              => 'pending',
                'type'                => 'signup',
            ]);

            $this->emailVerificationService->generateOtp($user);

            Log::info("Pending registration created: User ID {$user->id} ({$user->email}), Company ID {$company->id} ({$company->name})");

            return $user;
        });
    }

    /**
     * Build the Company payload from the validated wizard data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildCompanyData(array $data): array
    {
        $isLegal = $data['company_type'] === 'legal_entity';

        return [
            'company_type'            => $data['company_type'],
            'name'                    => $isLegal ? $data['company_name'] : $data['full_name'],
            'tax_id'                  => $data['tax_id'] ?? null,
            'legal_address'           => $data['legal_address'] ?? null,
            'address'                 => $data['address'] ?? null,
            'city'                    => $data['city'],
            'state_province'          => $data['state_province'],
            'postal_code'             => $data['postal_code'],
            'country'                 => $data['country'],
            'legal_entity_flag'       => $isLegal,
            'natural_entity_flag'     => ! $isLegal,
            'subscription_plan'       => $data['subscription_plan'] ?? 'basic',
            'subscription_status'     => 'pending_approval',
            'subscription_expires_at' => null,
        ];
    }

    /**
     * Build the User payload from the validated wizard data.
     *
     * @param  array<string, mixed>  $data
     * @param  int|string            $companyId
     * @return array<string, mixed>
     */
    private function buildUserData(array $data, int|string $companyId): array
    {
        $isLegal = $data['company_type'] === 'legal_entity';

        return [
            'company_id' => $companyId,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'role'       => $isLegal ? 'company_representative' : 'owner',
            'status'     => 'pending_verification',
        ];
    }
}
