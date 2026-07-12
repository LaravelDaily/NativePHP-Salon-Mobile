<?php

use App\Contracts\TokenStore;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('starts by asking for a phone number', function () {
    Livewire::test('pages::auth.otp')
        ->assertOk()
        ->assertSet('step', 'phone')
        ->assertSee('Send Code')
        ->assertDontSee('6-digit code');
});

it('sends a code and moves on to the code step', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300])]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+1 555-1234')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('step', 'code')
        ->assertSee('6-digit code')
        ->assertSee('We sent a code to your phone.');

    Http::assertSent(fn ($request): bool => $request['phone'] === '+1 555-1234');
});

it('shows the code inline when the API exposes it in development', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300, 'code' => '584524'])]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->assertSee('Development mode: your code is 584524');
});

it('starts the resend countdown and only offers resend once it runs out', function () {
    Http::fake(['*' => Http::response(['expires_in' => 300])]);

    $component = Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->assertSet('resendIn', 30)
        ->assertSee('Resend code in 30s')
        ->assertDontSee('Resend code<');

    foreach (range(1, 30) as $ignored) {
        $component->call('tickResend');
    }

    $component->assertSet('resendIn', 0)->assertSee('Resend code');
});

it('verifies the code, signs in and lands on home', function () {
    Http::fake([
        '*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300]),
        '*/api/v1/auth/otp/verify' => Http::response([
            'token' => 'issued-token',
            'user' => ['id' => 1, 'name' => 'Jane Tester'],
        ]),
    ]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->set('code', '584524')
        ->set('name', 'Jane Tester')
        ->call('verify')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(app(TokenStore::class)->get())->toBe('issued-token');

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), 'otp/verify')
        && $request['code'] === '584524'
        && $request['name'] === 'Jane Tester');
});

it('shows the API message and clears the field on a wrong code', function () {
    Http::fake([
        '*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300]),
        '*/api/v1/auth/otp/verify' => Http::response([
            'message' => 'That code is invalid or has expired.',
            'errors' => ['code' => ['That code is invalid or has expired.']],
        ], 422),
    ]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->set('code', '000000')
        ->call('verify')
        ->assertSee('That code is invalid or has expired.')
        ->assertSet('code', '')
        ->assertNoRedirect();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('requires a six digit code', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300])]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->set('code', '123')
        ->call('verify')
        ->assertHasErrors('code');
});

it('surfaces throttling from the API', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(status: 429)]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->assertSee("You've tried that too many times.")
        ->assertSet('step', 'phone');
});

it('lets the customer go back and change their number', function () {
    Http::fake(['*' => Http::response(['expires_in' => 300])]);

    Livewire::test('pages::auth.otp')
        ->set('phone', '+15551234567')
        ->call('sendCode')
        ->assertSet('step', 'code')
        ->call('changePhone')
        ->assertSet('step', 'phone')
        ->assertSet('code', '');
});
