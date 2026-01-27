<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize()
    {
        // Solo administradores pueden cambiar contraseñas
        // Asegúrate de que tu User model tenga el helper isAdmin(), si no, usa: $this->user()->role === 'administrador'
        return $this->user()->role === 'administrador'; 
    }

    public function rules()
    {
        return [
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.'
        ];
    }
}