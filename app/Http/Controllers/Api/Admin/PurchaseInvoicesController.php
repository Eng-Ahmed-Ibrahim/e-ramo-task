<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PurchaseInvoiceService;
use App\Http\Requests\StorePurchaseInvoiceRequest;

class PurchaseInvoicesController extends Controller
{
    public function __construct(
        private PurchaseInvoiceService $purchaseInvoiceService
    ) {}

    public function index(Request $request)
    {
        $invoices = $this->purchaseInvoiceService->getAll(
            $request->only([
                'branch_id',
                'warehouse_id',
                'status',
            ])
        );

        return response()->json([
            'status' => true,
            'message' => 'Purchase invoices retrieved successfully.',
            'data' => $invoices,
        ]);
    }

    public function store(StorePurchaseInvoiceRequest $request)
    {
        try {
            $invoice = $this->purchaseInvoiceService->create(
                $request->validated()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }
   

        return response()->json([
            'status' => true,
            'message' => 'Purchase invoice created successfully.',
            'data' => $invoice,
        ], 201);
    }

    public function show(int $id)
    {
        $invoice = $this->purchaseInvoiceService->find($id);

        return response()->json([
            'status' => true,
            'message' => 'Purchase invoice retrieved successfully.',
            'data' => $invoice,
        ]);
    }

    public function approve(int $id)
    {
        try {
            $invoice = $this->purchaseInvoiceService->approve($id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Purchase invoice approved successfully.',
            'data' => $invoice,
        ]);
    }

    public function cancel(int $id)
    {
        try {
            $invoice = $this->purchaseInvoiceService->cancel($id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Purchase invoice cancelled successfully.',
            'data' => $invoice,
        ]);
    }
}