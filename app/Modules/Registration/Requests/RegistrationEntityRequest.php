<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationEntityRequest extends FormRequest
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
            'company_name'   => $this->company_name ? strip_tags(trim($this->company_name)) : null,
            'tax_id'         => $this->tax_id ? trim($this->tax_id) : null,
            'legal_address'  => $this->legal_address ? strip_tags(trim($this->legal_address)) : null,
            'full_name'      => $this->full_name ? strip_tags(trim($this->full_name)) : null,
            'id_number'      => $this->id_number ? trim($this->id_number) : null,
            'address'        => $this->address ? strip_tags(trim($this->address)) : null,
            'city'           => $this->city ? strip_tags(trim($this->city)) : null,
            'state_province' => $this->state_province ? strip_tags(trim($this->state_province)) : null,
            'postal_code'    => $this->postal_code ? trim($this->postal_code) : null,
            'country'        => $this->country ? strip_tags(trim($this->country)) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * FR-002: Legal entity fields: company_name, tax_id, legal_address, city,
     *         state_province, postal_code, country
     * FR-003: Natural person fields: full_name, id_number, address, city,
     *         state_province, postal_code, country
     *
     * Shared fields (always required): city, state_province, postal_code, country
     *
     * Conditional fields are resolved at runtime via isLegalEntity() / isNaturalPerson()
     * so that T023 can extend this class or override these helpers without touching
     * the core rules() method.
     */
    public function rules(): array
    {
        $rules = [
            // Always required — shared by both paths (FR-002, FR-003)
            'city'           => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code'    => ['required', 'string', 'max:20'],
            'country'        => ['required', 'string', 'max:100'],
        ];

        if ($this->isLegalEntity()) {
            // FR-002: Legal entity specific fields
            $rules['company_name']   = ['required', 'string', 'max:255'];
            $rules['tax_id']         = ['required', 'string', 'max:100'];
            $rules['legal_address']  = ['required', 'string', 'max:500'];
        }

        if ($this->isNaturalPerson()) {
            // FR-003: Natural person specific fields
            $rules['full_name'] = ['required', 'string', 'max:255'];
            $rules['id_number'] = ['required', 'string', 'max:50'];
            $rules['address']   = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Get custom, user-friendly validation error messages.
     */
    public function messages(): array
    {
        return [
            // Shared address fields
            'city.required'           => 'The city field is required.',
            'city.string'             => 'The city must be a valid text value.',
            'city.max'                => 'The city name may not exceed 255 characters.',

            'state_province.required' => 'The state or province field is required.',
            'state_province.string'   => 'The state or province must be a valid text value.',
            'state_province.max'      => 'The state or province may not exceed 255 characters.',

            'postal_code.required'    => 'The postal code is required.',
            'postal_code.string'      => 'The postal code must be a valid text value.',
            'postal_code.max'         => 'The postal code may not exceed 20 characters.',

            'country.required'        => 'The country field is required.',
            'country.string'          => 'The country must be a valid text value.',
            'country.max'             => 'The country may not exceed 100 characters.',

            // Legal entity fields (FR-002)
            'company_name.required'   => 'The company name is required.',
            'company_name.string'     => 'The company name must be a valid text value.',
            'company_name.max'        => 'The company name may not exceed 255 characters.',

            'tax_id.required'         => 'The tax ID is required.',
            'tax_id.string'           => 'The tax ID must be a valid text value.',
            'tax_id.max'              => 'The tax ID may not exceed 100 characters.',

            'legal_address.required'  => 'The legal address is required.',
            'legal_address.string'    => 'The legal address must be a valid text value.',
            'legal_address.max'       => 'The legal address may not exceed 500 characters.',

            // Natural person fields (FR-003)
            'full_name.required'      => 'The full name is required.',
            'full_name.string'        => 'The full name must be a valid text value.',
            'full_name.max'           => 'The full name may not exceed 255 characters.',

            'id_number.required'      => 'The ID number is required.',
            'id_number.string'        => 'The ID number must be a valid text value.',
            'id_number.max'           => 'The ID number may not exceed 50 characters.',

            'address.required'        => 'The address is required.',
            'address.string'          => 'The address must be a valid text value.',
            'address.max'             => 'The address may not exceed 500 characters.',
        ];
    }

    /**
     * Get custom attribute names for more readable error messages.
     */
    public function attributes(): array
    {
        return [
            'company_name'   => 'company name',
            'tax_id'         => 'tax ID',
            'legal_address'  => 'legal address',
            'state_province' => 'state or province',
            'postal_code'    => 'postal code',
            'full_name'      => 'full name',
            'id_number'      => 'ID number',
        ];
    }

    /**
     * Determine whether this request is for a legal entity registration.
     *
     * Extracted to a method so T023 can override it or extend with additional logic
     * without modifying rules() directly.
     */
    protected function isLegalEntity(): bool
    {
        return ($this->input('company_type') ?? $this->session()->get('wizard_data.company_type')) === 'legal_entity';
    }

    /**
     * Determine whether this request is for a natural person registration.
     *
     * Extracted to a method so T023 can extend or override without touching rules().
     */
    protected function isNaturalPerson(): bool
    {
        return ($this->input('company_type') ?? $this->session()->get('wizard_data.company_type')) === 'natural_person';
    }
}
