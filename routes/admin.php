<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ToppingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'admin.home')->name('home');

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::get('users/create', [UserController::class, 'create'])->name('users.create');
Route::post('users', [UserController::class, 'store'])->name('users.store');
Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
Route::patch('users/{user}/status', [UserController::class, 'setStatus'])->name('users.status');

// Loaded by web.php inside the existing auth:web + role:admin group.
Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
    ->name('categories.toggle-status');
Route::resource('categories', CategoryController::class)->except('show');

Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
    ->name('products.toggle-status');
Route::resource('products', ProductController::class)->except('show');

Route::patch('product-variants/{product_variant}/toggle-status', [ProductVariantController::class, 'toggleStatus'])
    ->name('product-variants.toggle-status');
Route::resource('product-variants', ProductVariantController::class)->except('show');

Route::patch('toppings/{topping}/toggle-status', [ToppingController::class, 'toggleStatus'])
    ->name('toppings.toggle-status');
Route::resource('toppings', ToppingController::class)->except('show');

Route::patch('promotions/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus'])
    ->name('promotions.toggle-status');
Route::resource('promotions', PromotionController::class)->except('show');
