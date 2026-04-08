<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => "L'identifiant du technicien est obligatoire.",
            'technician_id.exists'   => "Le technicien sélectionné n'existe pas.",
        ];
    }
}
