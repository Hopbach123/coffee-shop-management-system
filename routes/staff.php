<?php

use App\Http\Controllers\Staff\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\WorkspaceController;

<?php

use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:staff')->group(function (): void {
    Route::get('/', [PosController::class, 'index'])->name('home');
    Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('/pos/remove', [PosController::class, 'remove'])->name('pos.remove');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/items', [OrderController::class, 'storeItem'])->name('orders.items.store');

    Route::get('/tables', [WorkspaceController::class, 'tables'])->name('tables.index');
    Route::get('/brewing', [WorkspaceController::class, 'brewing'])->name('brewing.index');
    Route::get('/reservations', [WorkspaceController::class, 'reservations'])->name('reservations.index');
    Route::get('/pickup-requests', [WorkspaceController::class, 'pickups'])->name('pickups.index');
    Route::patch('/pickup-requests/{pickupRequest}', [WorkspaceController::class, 'updatePickup'])->name('pickups.update');
});
