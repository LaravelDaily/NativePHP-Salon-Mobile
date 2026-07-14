<?php

use App\Http\Middleware\RequireCustomer;
use App\Http\Middleware\RequireGuest;
use App\NativeComponents\Appointments;
use App\NativeComponents\Auth\Otp;
use App\NativeComponents\Auth\Register;
use App\NativeComponents\Auth\Welcome;
use App\NativeComponents\Book;
use App\NativeComponents\Home;
use App\NativeComponents\Layouts\SalonStackLayout;
use App\NativeComponents\Layouts\SalonTabsLayout;
use Illuminate\Support\Facades\Route;

Route::middleware(RequireGuest::class)->group(function (): void {
    Route::native('/welcome', Welcome::class)->name('auth.welcome');
    Route::native('/register', Register::class)->name('auth.register');
    Route::native('/sign-in-with-code', Otp::class)->name('auth.otp');
});

Route::middleware(RequireCustomer::class)->group(function (): void {
    Route::nativeGroup(SalonTabsLayout::class, function (): void {
        Route::native('/', Home::class)->name('home');
        Route::native('/appointments', Appointments::class)->name('appointments');
        Route::native('/book', Book::class)->name('book');
    });

    // The booking screen doubles as the reschedule screen: same slot picker,
    // prefilled from the appointment being moved. Pushed on top of the tabs,
    // so it gets the stack chrome with a back chevron.
    Route::native('/appointments/{appointment}/reschedule', Book::class)
        ->layout(SalonStackLayout::class)
        ->name('appointments.reschedule');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::livewire('/profile/stylists', 'pages::stylists')->name('profile.stylists');
    Route::livewire('/settings', 'pages::settings')->name('settings');
    Route::livewire('/appointments/{appointment}/feedback', 'pages::feedback')
        ->name('appointments.feedback');
});
