<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "L'adresse email n'est pas valide.",
            'password.required' => "Le mot de passe est obligatoire.",
            'password.min'      => "Le mot de passe doit contenir au moins 6 caractères.",
        ];
    }
}
