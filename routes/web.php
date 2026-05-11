<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home');
Route::view('admin', 'admin.dashboard');
Route::view('login', 'login');
