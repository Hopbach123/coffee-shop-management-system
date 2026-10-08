<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'role:admin'])->group(base_path('routes/admin.php'));

Route::prefix('staff')->name('staff.')->middleware(['auth:web', 'role:admin,staff'])->group(base_path('routes/staff.php'));

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');
