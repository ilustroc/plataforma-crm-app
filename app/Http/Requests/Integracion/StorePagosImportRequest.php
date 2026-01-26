<?php

namespace App\Http\Requests\Integracion;

use Illuminate\Foundation\Http\FormRequest;

class StorePagosImportRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ];
    }
}
