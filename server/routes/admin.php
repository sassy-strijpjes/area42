<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('admin.')->prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::name('admin.')->prefix('admin')->middleware(['auth'])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('admins', 'admin.admins.index')->name('admins');
    Route::view('admins/create', 'admin.admins.create')->name('admins.create');
    Route::view('admins/{admin}/edit', 'admin.admins.edit')->name('admins.edit');
    Route::view('staff', 'admin.staff.index')->name('staff');
    Route::view('staff/create', 'admin.staff.create')->name('staff.create');
    Route::view('staff/{staff}/edit', 'admin.staff.edit')->name('staff.edit');
    Route::view('roles', 'admin.roles.index')->name('roles');
    Route::view('roles/create', 'admin.roles.create')->name('roles.create');
    Route::view('roles/{role}/edit', 'admin.roles.edit')->name('roles.edit');
    Route::view('logs', 'admin.logs')->name('logs');
    Route::get('login', [AuthController::class, 'type'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
