<?php

use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\CustomerOrderRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\WorkspaceController;


Route::middleware('role:admin,staff')->group(function (): void {
    Route::get('/', [PosController::class, 'index'])->name('home');
    Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('/pos/remove', [PosController::class, 'remove'])->name('pos.remove');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/customer-orders', [CustomerOrderRequestController::class, 'index'])->name('customer-orders.index');
    Route::get('/customer-orders/{customerRequest}', [CustomerOrderRequestController::class, 'show'])->name('customer-orders.show');
    Route::post('/customer-orders/{customerRequest}/accept', [CustomerOrderRequestController::class, 'accept'])->name('customer-orders.accept');
    Route::post('/customer-orders/{customerRequest}/prepare', [CustomerOrderRequestController::class, 'prepare'])->name('customer-orders.prepare');
    Route::post('/customer-orders/{customerRequest}/dispatch', [CustomerOrderRequestController::class, 'dispatch'])->name('customer-orders.dispatch');
    Route::post('/customer-orders/{customerRequest}/deliver', [CustomerOrderRequestController::class, 'deliver'])->name('customer-orders.deliver');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/payments', [PaymentController::class, 'show'])->name('orders.payments.show');
    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::post('orders/{order}/items', [OrderController::class, 'storeItem'])->name('orders.items.store');
    Route::patch('orders/{order}/items/{orderItem}', [OrderController::class, 'updateItem'])->name('orders.items.update');
    Route::patch('orders/{order}/items/{orderItem}/status', [OrderController::class, 'updateItemStatus'])->name('orders.items.status.update');
    Route::delete('orders/{order}/items/{orderItem}', [OrderController::class, 'destroyItem'])->name('orders.items.destroy');
    Route::post('orders/{order}/items/{orderItem}/toppings', [OrderController::class, 'storeItemTopping'])->name('orders.items.toppings.store');
    Route::patch('orders/{order}/items/{orderItem}/toppings/{orderItemTopping}', [OrderController::class, 'updateItemTopping'])->name('orders.items.toppings.update');
    Route::delete('orders/{order}/items/{orderItem}/toppings/{orderItemTopping}', [OrderController::class, 'destroyItemTopping'])->name('orders.items.toppings.destroy');

    Route::get('/tables', [WorkspaceController::class, 'tables'])->name('tables.index');
    Route::get('/brewing', [WorkspaceController::class, 'brewing'])->name('brewing.index');
    Route::get('/reservations', [WorkspaceController::class, 'reservations'])->name('reservations.index');
    Route::get('/pickup-requests', [WorkspaceController::class, 'pickups'])->name('pickups.index');
    Route::patch('/pickup-requests/{pickupRequest}', [WorkspaceController::class, 'updatePickup'])->name('pickups.update');
});
