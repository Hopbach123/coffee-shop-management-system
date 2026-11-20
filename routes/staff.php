<?php

use App\Http\Controllers\Staff\OnlineRequestController;
use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin,staff')->group(function (): void {
    Route::get('/', [PosController::class, 'index'])->name('home');
    Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('/pos/remove', [PosController::class, 'remove'])->name('pos.remove');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('online-requests', [OnlineRequestController::class, 'index'])->name('online-requests.index');
    Route::get('online-requests/{onlineRequest}', [OnlineRequestController::class, 'show'])->whereNumber('onlineRequest')->name('online-requests.show');
    Route::post('online-requests/{onlineRequest}/accept', [OnlineRequestController::class, 'accept'])->whereNumber('onlineRequest')->name('online-requests.accept');
    Route::post('online-requests/{onlineRequest}/reject', [OnlineRequestController::class, 'reject'])->whereNumber('onlineRequest')->name('online-requests.reject');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/payments', [OrderController::class, 'storePayment'])->name('orders.payments.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
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
