<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/admin.php';
require __DIR__ . '/staff.php';

Route::view('/', 'home')->name('home');
Route::view('book/restaurant', 'book.restaurant')->name('book.restaurant');
