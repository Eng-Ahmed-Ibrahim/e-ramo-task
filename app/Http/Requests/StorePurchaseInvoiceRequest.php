<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'exists:branches,id',
            ],

            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('branch_id', $this->branch_id),
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_invoices', 'invoice_number')
                    ->where('branch_id', $this->branch_id),
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'discount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'tax' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.unit_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.discount' => [
                'sometimes',
                'numeric',
                'min:0',
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
            'warehouse_id.exists' => 'The selected warehouse does not belong to the selected branch.',
            'invoice_number.unique' => 'This invoice number already exists in this branch.',
            'items.required' => 'At least one product is required.',
            'items.*.product_id.distinct' => 'Duplicate products are not allowed.',
        ];
    }
}
