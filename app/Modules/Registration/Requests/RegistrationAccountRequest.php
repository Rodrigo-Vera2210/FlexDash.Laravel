<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistrationAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Registration is open to all unauthenticated users.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'name'  => strip_tags(trim($this->name)),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * FR-004: Validate email format
     * FR-005: Validate email uniqueness in users table
     * FR-006: Validate password strength (min 8, mixed case, numbers, symbols)
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password'              => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Get custom, user-friendly validation error messages.
     */
    public function messages(): array
    {
        return [
            // Name
            'name.required'                  => 'The name field is required.',
            'name.string'                    => 'The name must be a valid string.',
            'name.max'                       => 'The name may not be greater than 255 characters.',

            // Email
            'email.required'                 => 'An email address is required.',
            'email.string'                   => 'The email must be a valid string.',
            'email.email'                    => 'Please enter a valid email address.',
            'email.max'                      => 'The email address may not be greater than 255 characters.',
            'email.unique'                   => 'This email address is already registered. Please log in or use a different email.',

            // Password
            'password.required'              => 'A password is required.',
            'password.string'                => 'The password must be a valid string.',
            'password.confirmed'             => 'The password confirmation does not match.',
            'password.min'                   => 'The password must be at least 8 characters long.',
            'password.mixed'                 => 'The password must contain at least one uppercase and one lowercase letter.',
            'password.numbers'               => 'The password must contain at least one number.',
            'password.symbols'               => 'The password must contain at least one special character (e.g. @, #, $, !).',

            // Password confirmation
            'password_confirmation.required' => 'Please confirm your password.',
            'password_confirmation.string'   => 'The password confirmation must be a valid string.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'name'                  => 'name',
            'email'                 => 'email address',
            'password'              => 'password',
            'password_confirmation' => 'password confirmation',
        ];
    }
}
