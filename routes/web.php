<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home.index');
});

Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/search/results', [MenuController::class, 'search'])->name('menu.search');
Route::get('/menu/{product:slug}', [MenuController::class, 'show'])->name('menu.show');

Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'role:admin'])->group(base_path('routes/admin.php'));

Route::prefix('staff')->name('staff.')->middleware(['auth:web', 'role:admin,staff'])->group(base_path('routes/staff.php'));

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');
