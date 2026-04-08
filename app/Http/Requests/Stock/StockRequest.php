<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stockId = $this->route('stock');

        return [
            'name'           => ['required', 'string', 'max:255'],
            'reference'      => ['nullable', 'string', Rule::unique('stocks', 'reference')->ignore($stockId)],
            'serial_number'  => ['nullable', 'string', Rule::unique('stocks', 'serial_number')->ignore($stockId)],
            'category'       => ['required', Rule::in([
                'ordinateur', 'imprimante', 'serveur',
                'reseau', 'peripherique', 'consommable', 'autre',
            ])],
            'description'    => ['nullable', 'string'],
            'quantity'       => ['required', 'integer', 'min:0'],
            'quantity_min'   => ['nullable', 'integer', 'min:0'],
            'status'         => ['nullable', Rule::in(['disponible', 'affecte', 'maintenance', 'hors_service'])],
            'location'       => ['nullable', 'string', 'max:255'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'model'          => ['nullable', 'string', 'max:100'],
            'purchase_date'  => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_end'   => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => "Le nom du matériel est obligatoire.",
            'category.required' => "La catégorie est obligatoire.",
            'quantity.required' => "La quantité est obligatoire.",
            'quantity.min'      => "La quantité ne peut pas être négative.",
        ];
    }
}
