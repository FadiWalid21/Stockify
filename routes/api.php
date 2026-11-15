<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);

    Route::get(
        '/email/verify/{id}/{hash}',
        VerifyEmailController::class
    )
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'requestOtp',
    ])->middleware('throttle:password-reset-request');

    Route::post('/verify-reset-otp', [
        PasswordResetController::class,
        'verifyOtp',
    ])->middleware('throttle:password-reset-verify');

    Route::post('/reset-password', [
        PasswordResetController::class,
        'resetPassword',
    ])->middleware('throttle:password-reset-submit');


    // Authenticated and tenant-scoped routes
    Route::middleware([
        'auth:sanctum',
        SetTenantContext::class,
    ])->group(function () {

        // Authentication
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);

        // Products
        Route::post(
            'products/{product}/deactivate',
            [ProductController::class, 'deactivate']
        );

        Route::post(
            'products/{product}/activate',
            [ProductController::class, 'activate']
        );

        Route::apiResource('products', ProductController::class);

        // Warehouses
        Route::post('warehouses/{warehouse}/deactivate', [
            WarehouseController::class,
            'deactivate',
        ]);

        Route::post('warehouses/{warehouse}/activate', [
            WarehouseController::class,
            'activate',
        ]);

        Route::apiResource('warehouses', WarehouseController::class);

        // Inventory
        Route::post(
            'inventory/stock-in',
            [InventoryController::class, 'stockIn'])
            ->middleware('permission:inventory.stock_in');

        Route::post('inventory/stock-out',
            [InventoryController::class, 'stockOut'])
            ->middleware('permission:inventory.stock_out');

        Route::post('inventory/opening-balance',
        [InventoryController::class, 'openingBalance'])
            ->middleware('permission:inventory.adjust');

        Route::post('inventory/adjustments',
        [InventoryController::class, 'adjust'])
            ->middleware('permission:inventory.adjust');

        Route::get('/inventory/balances',
        [InventoryController::class, 'balances'])
            ->middleware('permission:inventory.view');

        Route::get('/inventory/movements',
        [InventoryController::class, 'movements'])
            ->middleware('permission:inventory.view');

        // Categories
        Route::apiResource('categories', CategoryController::class);

        // Units
        Route::get('/units', [UnitController::class, 'index']);
    });
});
