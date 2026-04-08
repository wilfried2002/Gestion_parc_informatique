<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => "Le nom est obligatoire.",
            'email.required'     => "L'adresse email est obligatoire.",
            'email.unique'       => "Cette adresse email est déjà utilisée.",
            'password.required'  => "Le mot de passe est obligatoire.",
            'password.min'       => "Le mot de passe doit contenir au moins 8 caractères.",
            'password.confirmed' => "Les mots de passe ne correspondent pas.",
        ];
    }
}
