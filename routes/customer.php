<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Customer\OrdersController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'customerLogin');

    Route::middleware(['auth:sanctum', EnsureCustomer::class])->group(function () {
        Route::post('/logout', 'logout');
    });
});

Route::middleware(['auth:sanctum', EnsureCustomer::class])->group(function () {
    Route::prefix("orders")->group(function () {
        Route::post('/create-order', [OrdersController::class, 'create']);
        Route::get('/', [OrdersController::class, 'index']);
        Route::get('/{order}', [OrdersController::class, 'show']);
    });
});
