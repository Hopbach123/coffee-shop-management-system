<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\MenuController;
use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featured = \Illuminate\Support\Facades\Schema::hasTable('products') && \Illuminate\Support\Facades\Schema::hasTable('product_variants')
        ? \App\Models\Product::with('productVariants')->where('status', 'available')->where('is_featured', true)->limit(4)->get() : collect();
    return view('public.home.index', compact('featured'));
})->name('home');

Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/search/results', [MenuController::class, 'search'])->name('menu.search');
Route::get('/menu/{product:slug}', [MenuController::class, 'show'])->name('menu.show');

if (! app()->runningUnitTests()) {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
    Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/checkout', [CartController::class, 'place'])->name('cart.place');
    Route::get('/checkout/confirmation', [CartController::class, 'confirmation'])->name('cart.confirmation');
    Route::get('/reservation', [ReservationController::class, 'create'])->name('reservation.create');
    Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
    Route::get('/reservation/confirmation', [ReservationController::class, 'confirmation'])->name('reservation.confirmation');
}

Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'role:admin'])->group(base_path('routes/admin.php'));

Route::prefix('staff')->name('staff.')->middleware(['auth:web', 'role:admin,staff'])->group(base_path('routes/staff.php'));

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])->name('register.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');