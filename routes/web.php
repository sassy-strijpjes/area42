<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';

Route::name('staff.')->middleware(['auth'])->group(function () {
    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::name('staff.')->middleware(['auth'])->group(function () {
    Route::view('/', 'staff.dashboard')->name('dashboard');
    Route::view('roles', 'staff.roles.index')->name('roles');
    Route::view('roles/create', 'staff.roles.create')->name('roles.create');
    Route::view('roles/{role}/edit', 'staff.roles.edit')->name('roles.edit');
    Route::get('login', [AuthController::class, 'type'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
