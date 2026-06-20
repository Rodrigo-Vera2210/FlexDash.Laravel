<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_code.required' => 'The verification code is required.',
            'otp_code.size'     => 'The verification code must be exactly 6 digits.',
            'otp_code.regex'    => 'The verification code must contain only numbers.',
        ];
    }
}
