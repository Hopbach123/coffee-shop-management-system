<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\CafeTableController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ToppingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'admin.home')->name('home');

Route::get('tables', [CafeTableController::class, 'index'])->name('tables.index');
Route::get('tables/create', [CafeTableController::class, 'create'])->name('tables.create');
Route::post('tables', [CafeTableController::class, 'store'])->name('tables.store');
Route::get('tables/{cafe_table}/edit', [CafeTableController::class, 'edit'])->name('tables.edit');
Route::put('tables/{cafe_table}', [CafeTableController::class, 'update'])->name('tables.update');
Route::delete('tables/{cafe_table}', [CafeTableController::class, 'destroy'])->name('tables.destroy');
Route::patch('tables/{cafe_table}/status', [CafeTableController::class, 'setStatus'])->name('tables.status');

Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
Route::get('areas/create', [AreaController::class, 'create'])->name('areas.create');
Route::post('areas', [AreaController::class, 'store'])->name('areas.store');
Route::get('areas/{area}/edit', [AreaController::class, 'edit'])->name('areas.edit');
Route::put('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
Route::patch('areas/{area}/status', [AreaController::class, 'setStatus'])->name('areas.status');
Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

Route::patch('ingredients/{ingredient}/toggle-status', [IngredientController::class, 'toggleStatus'])
    ->name('ingredients.toggle-status');
Route::resource('ingredients', IngredientController::class)->except('show');

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::patch('customers/{customer}/status', [CustomerController::class, 'setStatus'])->name('customers.status');
Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

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
