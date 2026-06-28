<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'origin_branch_id'      => ['required', 'exists:branches,id,company_id,' . $companyId],
            'destination_branch_id' => [
                'required',
                'exists:branches,id,company_id,' . $companyId,
                'different:origin_branch_id'
            ],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'exists:products,id,company_id,' . $companyId],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_branch_id.different' => 'El local de destino debe ser diferente del local de origen.',
            'items.required'                  => 'Debe ingresar al menos un producto para realizar el traslado.',
            'items.*.quantity.min'            => 'La cantidad a trasladar debe ser mayor a cero.',
        ];
    }
}
