<?php

namespace App\Modules\Tickets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'evidence'    => ['required', 'array', 'min:1'],
            'evidence.*'  => ['required', 'file', 'image', 'max:10240'],
            'error_trace' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'Debe adjuntar al menos una imagen de evidencia.',
            'evidence.min'      => 'Debe adjuntar al menos una imagen de evidencia.',
            'evidence.*.image'  => 'El archivo de evidencia debe ser una imagen válida.',
            'evidence.*.max'    => 'La imagen de evidencia no debe superar los 10 MB.',
        ];
    }
}
