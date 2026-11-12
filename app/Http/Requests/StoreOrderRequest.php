<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required.',
            'customer_id.exists' => 'Customer not found.',
            'branch_id.required' => 'Branch ID is required.',
            'branch_id.exists' => 'Branch not found.',
            'idempotency_key.required' => 'Idempotency key is required.',
            'items.required' => 'Order items are required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product ID is required.',
            'items.*.product_id.distinct' => 'Duplicate products are not allowed.',
            'items.*.product_id.exists' => 'Product not found.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'status' => 'error',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}