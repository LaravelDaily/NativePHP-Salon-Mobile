<?php

use App\Contracts\TokenStore;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function fillRegistration(Testable $component): Testable
{
    return $component
        ->set('name', 'John Doe')
        ->set('email', 'johndoe@email.com')
        ->set('phone', '+1 555-1234')
        ->set('password', 'secret-password')
        ->set('passwordConfirmation', 'secret-password');
}

it('renders the create account screen with the fields from the mockup', function () {
    Livewire::test('pages::auth.register')
        ->assertOk()
        ->assertSee('Create Account')
        ->assertSee('Full Name')
        ->assertSee('Email Address')
        ->assertSee('Phone Number')
        ->assertSee('Confirm Password')
        ->assertSee('Sign Up');
});

it('registers a customer and signs them in', function () {
    Http::fake(['*/api/v1/auth/register' => Http::response([
        'token' => 'issued-token',
        'user' => ['id' => 1, 'name' => 'John Doe'],
    ], 201)]);

    fillRegistration(Livewire::test('pages::auth.register'))
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(app(TokenStore::class)->get())->toBe('issued-token');

    Http::assertSent(fn ($request): bool => $request['name'] === 'John Doe'
        && $request['phone'] === '+1 555-1234'
        && $request['password_confirmation'] === 'secret-password');
});

it('puts a server-side field error onto the right field', function () {
    Http::fake(['*/api/v1/auth/register' => Http::response([
        'message' => 'An account already exists for that email address.',
        'errors' => ['email' => ['An account already exists for that email address.']],
    ], 422)]);

    fillRegistration(Livewire::test('pages::auth.register'))
        ->call('register')
        ->assertHasErrors('email')
        ->assertSee('An account already exists for that email address.')
        ->assertNoRedirect();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('requires every field', function () {
    Http::fake();

    Livewire::test('pages::auth.register')
        ->call('register')
        ->assertHasErrors(['name', 'email', 'phone', 'password']);

    Http::assertNothingSent();
});

it('requires the two passwords to match', function () {
    Http::fake();

    fillRegistration(Livewire::test('pages::auth.register'))
        ->set('passwordConfirmation', 'something-else')
        ->call('register')
        ->assertHasErrors('password');

    Http::assertNothingSent();
});
