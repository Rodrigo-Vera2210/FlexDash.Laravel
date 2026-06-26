<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'OTP code is required.',
            'otp.size' => 'OTP code must be 6 digits.',
        ];
    }
}
