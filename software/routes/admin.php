<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('admin.')->prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::name('admin.')->prefix('admin')->middleware(['auth'])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('staff', 'admin.staff')->name('staff');
    Route::view('roles', 'admin.roles.index')->name('roles');
    Route::view('roles/create', 'admin.roles.create')->name('roles.create');
    Route::view('roles/{role}/edit', 'admin.roles.edit')->name('roles.edit');
    Route::view('logs', 'admin.logs')->name('logs');
    Route::get('login', [AuthController::class, 'type'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
