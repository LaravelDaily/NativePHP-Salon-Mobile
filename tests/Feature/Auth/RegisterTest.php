<?php

use App\Contracts\TokenStore;
use App\NativeComponents\Auth\Register;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;

function fillRegistration(TestableComponent $component): TestableComponent
{
    return $component
        ->input('name', 'John Doe')
        ->input('email', 'johndoe@email.com')
        ->input('phone', '+1 555-1234')
        ->input('password', 'secret-password')
        ->input('passwordConfirmation', 'secret-password');
}

it('renders the create account screen with the fields from the mockup', function () {
    Native::visit('/register')
        ->assertScreen(Register::class)
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

    fillRegistration(Native::test(Register::class))
        ->press('register')
        ->assertSet('fieldErrors', [])
        ->assertReplacedWith('/');

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

    fillRegistration(Native::test(Register::class))
        ->press('register')
        ->assertSet('fieldErrors', ['email' => 'An account already exists for that email address.'])
        ->assertSee('An account already exists for that email address.')
        ->assertNoNavigation();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('requires every field', function () {
    Http::fake();

    Native::test(Register::class)
        ->press('register')
        ->assertSee('The name field is required.')
        ->assertSee('The email field is required.')
        ->assertSee('The phone field is required.')
        ->assertSee('The password field is required.')
        ->assertNoNavigation();

    Http::assertNothingSent();
});

it('requires the two passwords to match', function () {
    Http::fake();

    fillRegistration(Native::test(Register::class))
        ->input('passwordConfirmation', 'something-else')
        ->press('register')
        ->assertSee('The password field must match password confirmation.')
        ->assertNoNavigation();

    Http::assertNothingSent();
});
