<?php

use App\Http\Controllers\AiPredictionController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/admin.php';

Route::post('ai/train', [AiPredictionController::class, 'train']);
Route::post('ai/predict', [AiPredictionController::class, 'predict']);

Route::name('staff.')->middleware(['auth'])->group(function () {
    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::name('staff.')->prefix('staff')->middleware(['auth'])->group(function () {
    Route::view('/', 'staff.dashboard')->name('dashboard');
    Route::view('roles', 'staff.roles.index')->middleware('permission:view_roles')->name('roles');
    Route::view('roles/create', 'staff.roles.create')->middleware('permission:add_roles')->name('roles.create');
    Route::view('roles/{role}/edit', 'staff.roles.edit')->middleware('permission:edit_roles')->name('roles.edit');
    Route::view('staff', 'staff.staff.index')->middleware('permission:view_staff')->name('staff');
    Route::view('staff/create', 'staff.staff.create')->middleware('permission:add_staff')->name('staff.create');
    Route::view('staff/{staff}/edit', 'staff.staff.edit')->middleware('permission:edit_staff')->name('staff.edit');
    Route::view('bookings/restaurant', 'staff.bookings.restaurant.index')->middleware('permission:view_restaurant-bookings')->name('restaurant.bookings');
    Route::view('bookings/restaurant/create', 'staff.bookings.restaurant.create')->middleware('permission:add_restaurant-bookings')->name('restaurant.bookings.create');
    Route::view('bookings/restaurant/{booking}/edit', 'staff.bookings.restaurant.edit')->middleware('permission:edit_restaurant-bookings')->name('restaurant.bookings.edit');
    Route::view('bookings/accommodation', 'staff.bookings.accommodation.index')->middleware('permission:view_accommodation-bookings')->name('accommodation.bookings');
    Route::view('bookings/accommodation/create', 'staff.bookings.accommodation.create')->middleware('permission:add_accommodation-bookings')->name('accommodation.bookings.create');
    Route::view('bookings/accommodation/{booking}/edit', 'staff.bookings.accommodation.edit')->middleware('permission:edit_accommodation-bookings')->name('accommodation.bookings.edit');
    Route::view('accommodation/types', 'staff.accommodation.types.index')->middleware('permission:view_accommodation-types')->name('accommodation.types');
    Route::view('accommodation/types/create', 'staff.accommodation.types.create')->middleware('permission:add_accommodation-types')->name('accommodation.types.create');
    Route::view('accommodation/types/{type}/edit', 'staff.accommodation.types.edit')->middleware('permission:edit_accommodation-types')->name('accommodation.types.edit');
    Route::view('accommodation/units', 'staff.accommodation.units.index')->middleware('permission:view_accommodation-units')->name('accommodation.units');
    Route::view('accommodation/units/create', 'staff.accommodation.units.create')->middleware('permission:add_accommodation-units')->name('accommodation.units.create');
    Route::view('accommodation/units/{unit}/edit', 'staff.accommodation.units.edit')->middleware('permission:edit_accommodation-units')->name('accommodation.units.edit');
    Route::view('accommodation/pricing', 'staff.accommodation.pricing.index')->middleware('permission:view_accommodation-pricing-rules')->name('accommodation.pricing');
    Route::view('accommodation/pricing/create', 'staff.accommodation.pricing.create')->middleware('permission:add_accommodation-pricing-rules')->name('accommodation.pricing.create');
    Route::view('accommodation/pricing/{rule}/edit', 'staff.accommodation.pricing.edit')->middleware('permission:edit_accommodation-pricing-rules')->name('accommodation.pricing.edit');
    Route::get('login', [AuthController::class, 'type'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
