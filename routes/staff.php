<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\WorkspaceController;

Route::get('/', [PosController::class, 'index'])->name('home');
Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
Route::post('/pos/remove', [PosController::class, 'remove'])->name('pos.remove');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
Route::get('/orders', [WorkspaceController::class, 'orders'])->name('orders.index');
Route::get('/tables', [WorkspaceController::class, 'tables'])->name('tables.index');
Route::get('/brewing', [WorkspaceController::class, 'brewing'])->name('brewing.index');
Route::get('/reservations', [WorkspaceController::class, 'reservations'])->name('reservations.index');
Route::get('/pickup-requests', [WorkspaceController::class, 'pickups'])->name('pickups.index');
Route::patch('/pickup-requests/{pickupRequest}', [WorkspaceController::class, 'updatePickup'])->name('pickups.update');
