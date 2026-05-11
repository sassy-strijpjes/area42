<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'staff.dashboard');
Route::view('login', 'login');
Route::view('admin', 'admin.dashboard');
