<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

require __DIR__ . '/admin.php';

Route::name('staff.')->prefix('staff')->group(function () {
    Route::get('login', [AuthController::class, 'type'])->name('login');
});

Route::name('staff.')->prefix('staff')->middleware(['auth'])->group(function () {
    Route::view('/', 'staff.dashboard')->name('dashboard');
});
