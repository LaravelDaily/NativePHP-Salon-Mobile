<?php

use App\Http\Middleware\RequireCustomer;
use App\Http\Middleware\RequireGuest;
use Illuminate\Support\Facades\Route;

Route::middleware(RequireGuest::class)->group(function (): void {
    Route::livewire('/welcome', 'pages::auth.welcome')->name('auth.welcome');
    Route::livewire('/register', 'pages::auth.register')->name('auth.register');
    Route::livewire('/sign-in-with-code', 'pages::auth.otp')->name('auth.otp');
});

Route::middleware(RequireCustomer::class)->group(function (): void {
    Route::livewire('/', 'pages::home')->name('home');
    Route::livewire('/appointments', 'pages::appointments')->name('appointments');
    Route::livewire('/book', 'pages::book')->name('book');
    Route::livewire('/profile', 'pages::profile')->name('profile');
});
