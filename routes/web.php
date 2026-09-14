<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home.index');
});

require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
