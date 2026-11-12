<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\OrdersController;
use App\Http\Controllers\Api\Admin\BranchesController;
use App\Http\Controllers\Api\Admin\ProductsController;
use App\Http\Controllers\Api\Admin\CustomersController;
use App\Http\Controllers\Api\Admin\CategoriesController;
use App\Http\Controllers\Api\Admin\WarehousesController;
use App\Http\Controllers\Api\Admin\PurchaseInvoicesController;


Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'adminLogin');

    Route::middleware(['auth:sanctum', EnsureAdmin::class])->group(function () {
        Route::post('/logout', 'logout');
    });
});

Route::middleware(['auth:sanctum', EnsureAdmin::class])->group(function () {
    Route::apiResource('branches', BranchesController::class);
    Route::apiResource('warehouses', WarehousesController::class);
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('products', ProductsController::class);
    Route::apiResource('customers', CustomersController::class);
    Route::prefix("invoices")->group(function () {
        Route::get('/', [PurchaseInvoicesController::class, 'index']);
        Route::post('/create-invoice', [PurchaseInvoicesController::class, 'store']);
        Route::post('/approve/{id}', [PurchaseInvoicesController::class, 'approve']);
        Route::post('/cancel/{id}', [PurchaseInvoicesController::class, 'cancel']);
    });
    Route::prefix("orders")->group(function () {
        Route::patch('/update-order-status/{id}', [OrdersController::class, 'updateStatus']);
    });
});
