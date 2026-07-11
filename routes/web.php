<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/appointments', 'pages::appointments')->name('appointments');
Route::livewire('/book', 'pages::book')->name('book');
Route::livewire('/profile', 'pages::profile')->name('profile');
