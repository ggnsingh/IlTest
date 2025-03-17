<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:500',
            'total_amount' => 'nullable|numeric|min:0',
            'order_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'items.min' => 'L\'ordine deve contenere almeno un prodotto',
            'items.*.product_id.exists' => 'Uno dei prodotti selezionati non esiste',
            'items.*.quantity.min' => 'La quantità deve essere almeno 1',
            'items.*.price.min' => 'Il prezzo non può essere negativo'
        ];
    }
}
