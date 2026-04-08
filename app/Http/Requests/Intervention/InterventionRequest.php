<?php

namespace App\Http\Requests\Intervention;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id'      => ['required', 'exists:tickets,id'],
            'description'    => ['required', 'string', 'min:10'],
            'technician_id'  => ['nullable', 'exists:users,id'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required'   => "Le ticket associé est obligatoire.",
            'ticket_id.exists'     => "Le ticket sélectionné n'existe pas.",
            'description.required' => "La description de l'intervention est obligatoire.",
            'end_date.after_or_equal' => "La date de fin doit être postérieure à la date de début.",
        ];
    }
}
