<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\check_login;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\WorkloadController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => ['check_login']], function () {
    Route::post("logout", [AuthController::class, "logout"])->name('logout');
    Route::get("get_news", [HomeController::class, "get_news"])->name('get_news');
    Route::get("get_birthday_records", [HomeController::class, "get_birthday_records"])->name('get_birthday_records');
    Route::get("get_leaves_records", [HomeController::class, "get_leaves_records"])->name('get_leaves_records');
    Route::get("get_upcoming_holiday", [HomeController::class, "get_upcoming_holiday"])->name('get_upcoming_holiday');
    Route::get("get_workload", [WorkloadController::class, "get_workload"])->name('get_workload');
    Route::post("get_timesheet", [TimesheetController::class, "get_timesheet"])->name('get_timesheet');

    Route::post('send_reset_link_email', [AuthController::class, 'send_reset_link_email'])->name('send_reset_link_email');
    Route::post('reset_password', [AuthController::class, 'reset_password'])->name('reset_password');

    Route::post('get_salary_records', [SalaryController::class, 'get_salary_records'])->name('get_salary_records');
});
