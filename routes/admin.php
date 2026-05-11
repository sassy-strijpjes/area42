<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::name('admin.')->prefix('admin')->group(function () {
    Route::get('login', [AuthController::class, 'type'])->name('login');
});

Route::name('admin.')->prefix('admin')->middleware(['auth'])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

