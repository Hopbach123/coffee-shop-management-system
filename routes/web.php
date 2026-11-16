<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\CustomerCartController;
use App\Http\Controllers\Public\CustomerOrderController;
use App\Http\Controllers\Public\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    try {
        $featured = \Illuminate\Support\Facades\Schema::hasTable('products') && \Illuminate\Support\Facades\Schema::hasTable('product_variants')
            ? \App\Models\Product::with('productVariants')->where('status', 'available')->where('is_featured', true)->limit(4)->get() : collect();
    } catch (\Throwable $e) {
        $featured = collect();
    }
    return view('public.home.index', compact('featured'));
})->name('home');

Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/search/results', [MenuController::class, 'search'])->name('menu.search');
Route::get('/menu/{product:slug}', [MenuController::class, 'show'])->name('menu.show');

Route::get('/cart', [CustomerCartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CustomerCartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{line}', [CustomerCartController::class, 'update'])->whereUuid('line')->name('cart.items.update');
Route::delete('/cart/items/{line}', [CustomerCartController::class, 'destroy'])->whereUuid('line')->name('cart.items.destroy');
Route::get('/cart/checkout', [CustomerOrderController::class, 'create'])->name('customer-orders.create');
Route::post('/customer-orders', [CustomerOrderController::class, 'store'])->middleware('throttle:customer-orders')->name('customer-orders.store');
Route::get('/customer-orders/{reference}', [CustomerOrderController::class, 'show'])->name('customer-orders.show');

Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'role:admin'])->group(base_path('routes/admin.php'));

Route::prefix('staff')->name('staff.')->middleware(['auth:web', 'role:admin,staff'])->group(base_path('routes/staff.php'));

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');
