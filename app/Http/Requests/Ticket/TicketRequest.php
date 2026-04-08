<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category'    => ['required', Rule::in(['materiel', 'logiciel', 'reseau', 'securite', 'autre'])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => "Le titre du ticket est obligatoire.",
            'title.min'            => "Le titre doit contenir au moins 5 caractères.",
            'description.required' => "La description est obligatoire.",
            'description.min'      => "La description doit contenir au moins 10 caractères.",
            'priority.required'    => "La priorité est obligatoire.",
            'priority.in'          => "La priorité doit être : low, medium, high ou critical.",
            'category.required'    => "La catégorie est obligatoire.",
            'category.in'          => "La catégorie doit être : materiel, logiciel, reseau, securite ou autre.",
        ];
    }
}
