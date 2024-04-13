<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\check_login;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => [check_login::class]], function () {
    Route::post("logout", [AuthController::class, "logout"])->name('logout');
});
