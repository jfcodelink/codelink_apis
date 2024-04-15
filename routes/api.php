<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HolidayController;
use App\Http\Middleware\check_login;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkloadController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => ['check_login']], function () {
    Route::post("logout", [AuthController::class, "logout"])->name('logout');

    Route::get("get_news", [HomeController::class, "get_news"])->name('get_news');
    Route::get("get_birthday_records", [HomeController::class, "get_birthday_records"])->name('get_birthday_records');
    Route::get("get_leaves_records", [HomeController::class, "get_leaves_records"])->name('get_leaves_records');
    Route::get("get_upcoming_holiday", [HomeController::class, "get_upcoming_holiday"])->name('get_upcoming_holiday');

    Route::get("get_workload", [WorkloadController::class, "get_workload"])->name('get_workload');
    Route::post("update_workload", [WorkloadController::class, "update_workload"])->name('update_workload');

    Route::post("get_timesheet", [TimesheetController::class, "get_timesheet"])->name('get_timesheet');

    Route::get("get_leaves", [LeaveController::class, "get_leaves"])->name('get_leaves');
    Route::post("delete_leave", [LeaveController::class, "delete_leave"])->name('delete_leave');

    Route::get("get_user_data", [UserController::class, "get_user_data"])->name('get_user_data');
    Route::get("get_user_guides", [UserController::class, "get_user_guides"])->name('get_user_guides');

    Route::get("get_policies", [PolicyController::class, "get_policies"])->name('get_policies');
    Route::get("get_holidays", [HolidayController::class, "get_holidays"])->name('get_holidays');
});
