<?php

use Illuminate\Support\Facades\Http;

it('sends a signed-out customer to the welcome screen', function (string $route) {
    $this->get(route($route))->assertRedirect(route('auth.welcome'));
})->with(['home', 'appointments', 'book', 'profile']);

it('lets a signed-out customer reach the auth screens', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['auth.welcome', 'auth.register', 'auth.otp']);

it('lets a signed-in customer through to the app', function () {
    Http::fake([
        '*/api/v1/salon' => Http::response(['data' => ['name' => 'StyleHub']]),
        // Home also asks for the next appointment; an unfaked URL would be sent
        // for real and hang the suite.
        '*' => Http::response(['data' => []]),
    ]);

    $this->withSession(customerSession())->get(route('home'))->assertOk();
});

it('keeps a signed-in customer out of the auth screens', function (string $route) {
    $this->withSession(customerSession())->get(route($route))->assertRedirect(route('home'));
})->with(['auth.welcome', 'auth.register', 'auth.otp']);
