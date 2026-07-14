<?php

use App\Http\Middleware\RequireCustomer;
use App\Http\Middleware\RequireGuest;
use App\NativeComponents\Auth\Register;
use App\NativeComponents\Auth\Welcome;
use Illuminate\Support\Facades\Route;

Route::middleware(RequireGuest::class)->group(function (): void {
    Route::native('/welcome', Welcome::class)->name('auth.welcome');
    Route::native('/register', Register::class)->name('auth.register');
    Route::livewire('/sign-in-with-code', 'pages::auth.otp')->name('auth.otp');
});

Route::middleware(RequireCustomer::class)->group(function (): void {
    Route::livewire('/', 'pages::home')->name('home');
    Route::livewire('/appointments', 'pages::appointments')->name('appointments');
    Route::livewire('/book', 'pages::book')->name('book');

    // The booking screen doubles as the reschedule screen: same slot picker,
    // prefilled from the appointment being moved.
    Route::livewire('/appointments/{appointment}/reschedule', 'pages::book')
        ->name('appointments.reschedule');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::livewire('/profile/stylists', 'pages::stylists')->name('profile.stylists');
    Route::livewire('/settings', 'pages::settings')->name('settings');
    Route::livewire('/appointments/{appointment}/feedback', 'pages::feedback')
        ->name('appointments.feedback');
});
