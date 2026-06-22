<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/admin.php';
require __DIR__ . '/staff.php';

Route::view('/', 'home')->name('home');
Route::livewire('book/restaurant', 'forms.book.restaurant')->name('book.restaurant');
Route::livewire('book/accommodation', 'forms.book.accommodation')->name('book.accommodation');
