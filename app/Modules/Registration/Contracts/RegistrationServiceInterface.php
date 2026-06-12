<?php

namespace App\Modules\Registration\Contracts;

use App\Models\User;

/**
 * Contract for the Registration Service.
 *
 * Defines the public API that the Application Layer exposes for creating
 * new registrations. All callers (controllers, jobs, tests) MUST depend on
 * this interface, never on the concrete implementation.
 *
 * Constitution rule: Cross-layer communication flows through defined interfaces
 * only. No implementation details belong here.
 */
interface RegistrationServiceInterface
{
    /**
     * Create a pending registration for a new user.
     *
     * Orchestrates the full registration bootstrap inside a single database
     * transaction:
     *  1. Creates a `Company` record with the supplied company-type and address data.
     *  2. Creates a `User` record associated with that company, setting the
     *     appropriate role (`company_representative` or `owner`) and the initial
     *     `status = pending_verification`.
     *  3. Creates an `EmailVerification` record containing the hashed OTP and
     *     its expiry timestamp.
     *  4. Dispatches the OTP notification to the user's email address.
     *
     * If any step fails the entire transaction is rolled back, ensuring no
     * partial registration data is persisted.
     *
     * @param  array<string, mixed>  $data  Validated wizard payload containing
     *                                      at minimum:
     *                                      - company_type (string)
     *                                      - name (string)
     *                                      - email (string)
     *                                      - password (string, plain-text pre-hash)
     *                                      - address fields (city, state_province,
     *                                        postal_code, country, etc.)
     *
     * @return \App\Models\User  The newly created, unverified user instance.
     *
     * @throws \Throwable  Re-throws any exception caught during the transaction
     *                     so the caller can handle presentation-level errors.
     */
    public function createPendingRegistration(array $data): User;
}
