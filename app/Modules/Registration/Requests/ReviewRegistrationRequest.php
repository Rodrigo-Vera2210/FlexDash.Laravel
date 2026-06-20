<?php

namespace App\Modules\Registration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
