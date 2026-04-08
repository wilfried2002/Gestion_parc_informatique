<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class AffectationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'  => ['required', 'exists:users,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'  => "L'utilisateur est obligatoire.",
            'user_id.exists'    => "L'utilisateur sélectionné n'existe pas.",
            'quantity.required' => "La quantité est obligatoire.",
            'quantity.min'      => "La quantité doit être d'au moins 1.",
        ];
    }
}
