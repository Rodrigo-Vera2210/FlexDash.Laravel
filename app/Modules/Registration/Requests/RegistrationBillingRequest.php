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
            'subscription_plan'               => ['required', 'string', 'in:basic,standard,premium'],
            'subscription_duration_months'      => ['required', 'integer', 'in:1,3,6,12,24,36'],
            'subscription_amount'             => ['required', 'numeric', 'min:0'],
            'subscription_discount_percentage'=> ['required', 'numeric', 'min:0', 'max:100'],
            'bank_origin'                     => ['required', 'string', 'max:255'],
            'account_destination'             => ['required', 'string', 'max:255'],
            'payment_receipt'                 => ['required', 'file', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_plan.required'                  => 'Debe seleccionar un plan de suscripción.',
            'subscription_plan.in'                      => 'El plan de suscripción seleccionado no es válido.',
            'subscription_duration_months.required'     => 'Debe seleccionar la duración de la suscripción.',
            'subscription_duration_months.in'           => 'La duración seleccionada no es válida.',
            'subscription_amount.required'              => 'El monto total es requerido.',
            'subscription_discount_percentage.required' => 'El descuento aplicado es requerido.',
            'bank_origin.required'                      => 'Debe ingresar el banco de origen.',
            'account_destination.required'              => 'Debe seleccionar la cuenta de destino.',
            'payment_receipt.required'                  => 'Debe subir la captura/imagen del comprobante de depósito.',
            'payment_receipt.image'                     => 'El archivo del comprobante debe ser una imagen válida (JPEG, PNG).',
            'payment_receipt.max'                       => 'El archivo del comprobante no debe superar los 4MB.',
        ];
    }
}
