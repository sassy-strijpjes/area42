<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

require __DIR__ . '/admin.php';

Route::name('staff.')->middleware([Auth::class])->group(function () {
    Route::view('/', 'staff.dashboard')->name('dashboard');
    Route::get('staff/login', [AuthController::class, 'type'])->name('login');
});
