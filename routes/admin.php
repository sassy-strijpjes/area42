<?php

use App\Http\Middleware\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::name('admin.')->prefix('admin')->middleware([Auth::class])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::get('login', [AuthController::class, 'type'])->name('login');
});
