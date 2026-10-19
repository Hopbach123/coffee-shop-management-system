<?php

use App\Http\Controllers\Staff\OrderController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'staff.home')->name('home');

Route::middleware('role:staff')->group(function (): void {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});
