<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectCompanyTypeRequest extends FormRequest
{
    /**
     * No authentication required for the registration flow.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate that a recognised company type has been selected (FR-001).
     */
    public function rules(): array
    {
        return [
            'company_type' => ['required', 'string', 'in:legal_entity,natural_person'],
        ];
    }

    /**
     * Human-readable error messages for company_type validation failures.
     */
    public function messages(): array
    {
        return [
            'company_type.required' => 'Please select a company type to continue.',
            'company_type.in'       => 'The selected company type is invalid. Please choose "Legal Entity" or "Natural Person".',
        ];
    }
}
