<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan'   => ['required', 'string', 'in:basic,standard'],
            'bank_origin'         => ['required', 'string', 'max:255'],
            'account_destination' => ['required', 'string', 'max:255'],
            'payment_receipt'     => ['required', 'file', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_plan.required'   => 'Debe seleccionar un plan de suscripción.',
            'subscription_plan.in'         => 'El plan de suscripción seleccionado no es válido.',
            'bank_origin.required'         => 'Debe ingresar el banco de origen.',
            'account_destination.required' => 'Debe seleccionar la cuenta de destino.',
            'payment_receipt.required'     => 'Debe subir la captura/imagen del comprobante de depósito.',
            'payment_receipt.image'        => 'El archivo del comprobante debe ser una imagen válida (JPEG, PNG).',
            'payment_receipt.max'          => 'El archivo del comprobante no debe superar los 4MB.',
        ];
    }
}
