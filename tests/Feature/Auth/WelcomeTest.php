<?php

use App\Contracts\TokenStore;
use App\NativeComponents\Auth\Welcome;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

it('renders the sign-in screen', function () {
    Native::visit('/welcome')
        ->assertScreen(Welcome::class)
        ->assertSee('Welcome Back')
        ->assertSee('Sign In')
        ->assertSee('Sign in with a code');
});

it('signs a customer in and stores the token', function () {
    Http::fake(['*/api/v1/auth/login' => Http::response([
        'token' => 'issued-token',
        'user' => ['id' => 1, 'name' => 'John Doe'],
    ])]);

    Native::test(Welcome::class)
        ->input('email', 'john@example.com')
        ->input('password', 'secret-password')
        ->press('signIn')
        ->assertSet('fieldErrors', [])
        ->assertReplacedWith('/');

    expect(app(TokenStore::class)->get())->toBe('issued-token');

    Http::assertSent(fn ($request): bool => $request['email'] === 'john@example.com'
        && $request['password'] === 'secret-password');
});

it('shows the API error and keeps the customer signed out on bad credentials', function () {
    Http::fake(['*/api/v1/auth/login' => Http::response([
        'message' => 'Those credentials do not match our records.',
        'errors' => ['email' => ['Those credentials do not match our records.']],
    ], 422)]);

    Native::test(Welcome::class)
        ->input('email', 'john@example.com')
        ->input('password', 'wrong-password')
        ->press('signIn')
        ->assertSee('Those credentials do not match our records.')
        ->assertSet('password', '')
        ->assertNoNavigation();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('validates before calling the API', function () {
    Http::fake();

    Native::test(Welcome::class)
        ->input('email', 'not-an-email')
        ->press('signIn')
        ->assertSee('The email field must be a valid email address.')
        ->assertSee('The password field is required.')
        ->assertNoNavigation();

    Http::assertNothingSent();
});

it('reports an unreachable API without signing in', function () {
    Http::fake(fn () => throw new ConnectionException('refused'));

    Native::test(Welcome::class)
        ->input('email', 'john@example.com')
        ->input('password', 'secret-password')
        ->press('signIn')
        ->assertSee("Can't reach StyleHub right now.")
        ->assertNoNavigation();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('navigates to the OTP and register screens', function () {
    Native::test(Welcome::class)
        ->press('goToOtp')
        ->assertNavigatedTo('/sign-in-with-code');

    Native::test(Welcome::class)
        ->press('goToRegister')
        ->assertNavigatedTo('/register');
});
