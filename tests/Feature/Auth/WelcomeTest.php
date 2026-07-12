<?php

use App\Contracts\TokenStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders the sign-in screen', function () {
    Livewire::test('pages::auth.welcome')
        ->assertOk()
        ->assertSee('Welcome Back')
        ->assertSee('Sign In')
        ->assertSee('Sign in with a code');
});

it('signs a customer in and stores the token', function () {
    Http::fake(['*/api/v1/auth/login' => Http::response([
        'token' => 'issued-token',
        'user' => ['id' => 1, 'name' => 'John Doe'],
    ])]);

    Livewire::test('pages::auth.welcome')
        ->set('email', 'john@example.com')
        ->set('password', 'secret-password')
        ->call('signIn')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(app(TokenStore::class)->get())->toBe('issued-token');

    Http::assertSent(fn ($request): bool => $request['email'] === 'john@example.com'
        && $request['password'] === 'secret-password');
});

it('shows the API error and keeps the customer signed out on bad credentials', function () {
    Http::fake(['*/api/v1/auth/login' => Http::response([
        'message' => 'Those credentials do not match our records.',
        'errors' => ['email' => ['Those credentials do not match our records.']],
    ], 422)]);

    Livewire::test('pages::auth.welcome')
        ->set('email', 'john@example.com')
        ->set('password', 'wrong-password')
        ->call('signIn')
        ->assertSee('Those credentials do not match our records.')
        ->assertSet('password', '')
        ->assertNoRedirect();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('validates before calling the API', function () {
    Http::fake();

    Livewire::test('pages::auth.welcome')
        ->set('email', 'not-an-email')
        ->set('password', '')
        ->call('signIn')
        ->assertHasErrors(['email', 'password']);

    Http::assertNothingSent();
});

it('reports an unreachable API without signing in', function () {
    Http::fake(fn () => throw new ConnectionException('refused'));

    Livewire::test('pages::auth.welcome')
        ->set('email', 'john@example.com')
        ->set('password', 'secret-password')
        ->call('signIn')
        ->assertSee("Can't reach StyleHub right now.")
        ->assertNoRedirect();

    expect(app(TokenStore::class)->has())->toBeFalse();
});
