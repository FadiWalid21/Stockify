<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PurchaseReturnController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\SalesReturnController;
use App\Http\Controllers\Api\V1\StockTransferController;
use App\Http\Controllers\Api\V1\SupplierController;
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

        Route::get('/transfers',
        [StockTransferController::class, 'index'])
            ->middleware('permission:transfers.view');

        Route::post('/transfers',
        [StockTransferController::class, 'store'])
            ->middleware('permission:transfers.create');

        Route::get('/transfers/{transferId}',
        [StockTransferController::class, 'show'])
            ->middleware('permission:transfers.view');

        Route::post('/transfers/{transferId}/approve',
        [StockTransferController::class, 'approve'])
            ->middleware('permission:transfers.approve');

        Route::post('/transfers/{transferId}/dispatch',
        [StockTransferController::class, 'dispatch'])
            ->middleware('permission:transfers.dispatch');

        Route::post('/transfers/{transferId}/receive',
        [StockTransferController::class, 'receive'])
            ->middleware('permission:transfers.receive');

        Route::post('/transfers/{transferId}/cancel',
        [StockTransferController::class, 'cancel'])
            ->middleware('permission:transfers.approve');

            // Suppliers
        Route::get('/suppliers',
        [SupplierController::class, 'index'])
            ->middleware('permission:suppliers.view');

        Route::post('/suppliers',
        [SupplierController::class, 'store'])
            ->middleware('permission:suppliers.create');

        Route::get('/suppliers/{supplier}',
        [SupplierController::class, 'show'])
            ->middleware('permission:suppliers.view');

        Route::put('/suppliers/{supplier}',
        [SupplierController::class, 'update'])
            ->middleware('permission:suppliers.update');

        Route::patch('/suppliers/{supplier}',
        [SupplierController::class, 'update'])
            ->middleware('permission:suppliers.update');

        Route::delete('/suppliers/{supplier}',
        [SupplierController::class, 'destroy'])
            ->middleware('permission:suppliers.delete');

        Route::patch('/suppliers/{supplier}/activate',
        [SupplierController::class, 'activate'])
            ->middleware('permission:suppliers.update');

        Route::patch('/suppliers/{supplier}/deactivate',
        [SupplierController::class, 'deactivate'])
            ->middleware('permission:suppliers.update');

        Route::get('/purchase-orders',
        [PurchaseOrderController::class, 'index'])
            ->middleware('permission:purchase_orders.view');

        Route::post('/purchase-orders',
        [PurchaseOrderController::class, 'store'])
            ->middleware('permission:purchase_orders.create');

        Route::get('/purchase-orders/{purchaseOrder}',
        [PurchaseOrderController::class, 'show'])
            ->middleware('permission:purchase_orders.view');

        Route::match(['put', 'patch'], '/purchase-orders/{purchaseOrder}',
        [PurchaseOrderController::class, 'update'])
            ->middleware('permission:purchase_orders.update');

        Route::post('/purchase-orders/{purchaseOrder}/approve',
        [PurchaseOrderController::class, 'approve'])
            ->middleware('permission:purchase_orders.approve');

        Route::post('/purchase-orders/{purchaseOrder}/cancel',
        [PurchaseOrderController::class, 'cancel'])
            ->middleware('permission:purchase_orders.cancel');

        Route::post('/purchase-orders/{purchaseOrder}/receive',
        [PurchaseOrderController::class, 'receive'])
            ->middleware('permission:purchase_orders.receive');

            // Customers
        Route::get('/customers',
        [CustomerController::class, 'index'])
            ->middleware('permission:customers.view');

        Route::post('/customers',
        [CustomerController::class, 'store'])
            ->middleware('permission:customers.create');

        Route::get('/customers/{customer}',
        [CustomerController::class, 'show'])
            ->middleware('permission:customers.view');

        Route::put('/customers/{customer}',
        [CustomerController::class, 'update'])
            ->middleware('permission:customers.update');

        Route::patch('/customers/{customer}',
        [CustomerController::class, 'update'])
            ->middleware('permission:customers.update');

        Route::delete('/customers/{customer}',
        [CustomerController::class, 'destroy'])
            ->middleware('permission:customers.delete');

        Route::patch('/customers/{customer}/activate',
        [CustomerController::class, 'activate'])
            ->middleware('permission:customers.activate');

        Route::patch('/customers/{customer}/deactivate',
        [CustomerController::class, 'deactivate'])
            ->middleware('permission:customers.deactivate');

        Route::get('/sales-orders',
        [SalesOrderController::class, 'index'])
            ->middleware('permission:sales_orders.view');

        Route::post('/sales-orders',
        [SalesOrderController::class, 'store'])
            ->middleware('permission:sales_orders.create');

        Route::get('/sales-orders/{salesOrderId}',
        [SalesOrderController::class, 'show'])
            ->middleware('permission:sales_orders.view');

        Route::put('/sales-orders/{salesOrderId}',
        [SalesOrderController::class, 'update'])
            ->middleware('permission:sales_orders.update');

        Route::patch('/sales-orders/{salesOrderId}',
        [SalesOrderController::class, 'update'])
            ->middleware('permission:sales_orders.update');

        Route::post('/sales-orders/{salesOrderId}/place',
        [SalesOrderController::class, 'place'])
            ->middleware('permission:sales_orders.place');

        Route::post('/sales-orders/{salesOrderId}/confirm',
        [SalesOrderController::class, 'confirm'])
            ->middleware('permission:sales_orders.confirm');

        Route::post('/sales-orders/{salesOrderId}/cancel',
        [SalesOrderController::class, 'cancel'])
            ->middleware('permission:sales_orders.cancel');

        Route::post('/sales-orders/{salesOrderId}/dispatch',
        [SalesOrderController::class, 'dispatch'])
            ->middleware('permission:sales_orders.dispatch');

        // Purchase Returns
        Route::get('/purchase-returns',
        [PurchaseReturnController::class, 'index'])
            ->middleware('permission:purchase_returns.view');

        Route::post('/purchase-returns',
        [PurchaseReturnController::class, 'store'])
            ->middleware('permission:purchase_returns.create');

        Route::get('/purchase-returns/eligibility/{productId}',
        [PurchaseReturnController::class, 'eligibility'])
            ->middleware('permission:purchase_returns.view');

        Route::get('/purchase-returns/{purchaseReturnId}',
        [PurchaseReturnController::class, 'show'])
            ->middleware('permission:purchase_returns.view');

        Route::post('/purchase-returns/{purchaseReturnId}/approve',
        [PurchaseReturnController::class, 'approve'])
            ->middleware('permission:purchase_returns.approve');

        Route::post('/purchase-returns/{purchaseReturnId}/dispatch',
        [PurchaseReturnController::class, 'dispatch'])
            ->middleware('permission:purchase_returns.dispatch');

        Route::post('/purchase-returns/{purchaseReturnId}/cancel',
        [PurchaseReturnController::class, 'cancel'])
            ->middleware('permission:purchase_returns.cancel');

        // Sales Returns
        Route::get('/sales-returns',
        [SalesReturnController::class, 'index'])
            ->middleware('permission:sales_returns.view');

        Route::post('/sales-returns',
        [SalesReturnController::class, 'store'])
            ->middleware('permission:sales_returns.create');

        Route::get('/sales-returns/eligible-items',
        [SalesReturnController::class, 'eligibleItems'])
            ->middleware('permission:sales_returns.view');

        Route::get('/sales-returns/{salesReturn}',
        [SalesReturnController::class, 'show'])
            ->middleware('permission:sales_returns.view');

        Route::post('/sales-returns/{salesReturn}/approve',
        [SalesReturnController::class, 'approve'])
            ->middleware('permission:sales_returns.approve');

        Route::post('/sales-returns/{salesReturn}/receive',
        [SalesReturnController::class, 'receive'])
            ->middleware('permission:sales_returns.receive');

        Route::post('/sales-returns/{salesReturn}/complete',
        [SalesReturnController::class, 'complete'])
            ->middleware('permission:sales_returns.complete');

        Route::post('/sales-returns/{salesReturn}/cancel',
        [SalesReturnController::class, 'cancel'])
            ->middleware('permission:sales_returns.cancel');

        // Categories
        Route::apiResource('categories', CategoryController::class);

        // Units
        Route::get('/units', [UnitController::class, 'index']);
    });
});
