<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/artisan-clear', function () {
    \Artisan::call('optimize:clear');
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
});
