<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->role === 'administrador';
    }

    public function rules()
    {
        // Detectamos si estamos reasignando (si viene supervisor_id)
        if ($this->has('supervisor_id')) {
            return [
                'supervisor_id' => 'required|exists:users,id',
            ];
        }

        // Reglas por defecto si en el futuro agregas "Editar Usuario" (Nombre/Email)
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $this->route('user')->id,
        ];
    }
}