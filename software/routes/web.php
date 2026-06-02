<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/admin.php';

Route::name('staff.')->middleware(['auth'])->group(function () {
    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::name('staff.')->middleware(['auth'])->group(function () {
    Route::view('/', 'staff.dashboard')->name('dashboard');
    Route::view('roles', 'staff.roles.index')->middleware('permission:view_roles')->name('roles');
    Route::view('roles/create', 'staff.roles.create')->middleware('permission:add_roles')->name('roles.create');
    Route::view('roles/{role}/edit', 'staff.roles.edit')->middleware('permission:edit_roles')->name('roles.edit');
    Route::view('staff', 'staff.staff.index')->middleware('permission:view_staff')->name('staff');
    Route::view('staff/create', 'staff.staff.create')->middleware('permission:add_staff')->name('staff.create');
    Route::view('staff/{staff}/edit', 'staff.staff.edit')->middleware('permission:edit_staff')->name('staff.edit');
    Route::view('bookings/restaurant', 'staff.bookings.restaurant.index')->middleware('permission:view_restaurant-bookings')->name('restaurant.bookings');
    Route::get('login', [AuthController::class, 'type'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
