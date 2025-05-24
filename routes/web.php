<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// مسیر نمونه برای بنر تخفیف
Route::get('/examples/discount-banner', function () {
    return view('examples.discount-banner-example');
});
