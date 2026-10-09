<?php

use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

// Loaded by web.php inside the existing auth:web + role:admin group.
Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
    ->name('categories.toggle-status');
Route::resource('categories', CategoryController::class)->except('show');
