<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(OrderStatus::class),
            ],
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
    public function messages(): array
    {
        return [
            'status.required' => 'Order status is required.',
            'status.string' => 'Order status must be a valid string.',
            'status.enum' => 'Invalid order status. Allowed values: pending, confirmed, processing, completed, cancelled.',
        ];
    }
}
