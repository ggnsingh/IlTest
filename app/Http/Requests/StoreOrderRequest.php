<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:500',
            'total_amount' => 'required|numeric|min:0',
            'order_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0'
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
            'items.required' => 'L\'ordine deve contenere almeno un prodotto',
            'items.*.product_id.exists' => 'Uno dei prodotti selezionati non esiste',
            'items.*.quantity.min' => 'La quantità deve essere almeno 1',
            'items.*.price.min' => 'Il prezzo non può essere negativo'
        ];
    }

}
