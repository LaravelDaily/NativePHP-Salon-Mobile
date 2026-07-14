<?php

use App\Contracts\TokenStore;
use App\NativeComponents\Auth\Otp;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

it('starts by asking for a phone number', function () {
    Native::visit('/sign-in-with-code')
        ->assertScreen(Otp::class)
        ->assertSet('step', 'phone')
        ->assertSee('Send Code')
        ->assertDontSee('6-digit code');
});

it('sends a code and moves on to the code step', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300])]);

    Native::test(Otp::class)
        ->input('phone', '+1 555-1234')
        ->press('sendCode')
        ->assertSet('fieldErrors', [])
        ->assertSet('step', 'code')
        ->assertSee('6-digit code')
        ->assertSee('We sent a code to your phone.');

    Http::assertSent(fn ($request): bool => $request['phone'] === '+1 555-1234');
});

it('shows the code inline when the API exposes it in development', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300, 'code' => '584524'])]);

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->assertSee('Development mode: your code is 584524');
});

it('starts the resend countdown and only offers resend once it runs out', function () {
    Http::fake(['*' => Http::response(['expires_in' => 300])]);

    $component = Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->assertSet('resendIn', 30)
        ->assertSee('Resend code in 30s');

    foreach (range(1, 30) as $ignored) {
        $component->firePoll('tickResend');
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

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->input('code', '584524')
        ->input('name', 'Jane Tester')
        ->press('verify')
        ->assertSet('fieldErrors', [])
        ->assertReplacedWith('/');

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

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->input('code', '000000')
        ->press('verify')
        ->assertSee('That code is invalid or has expired.')
        ->assertSet('code', '')
        ->assertNoNavigation();

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('requires a six digit code', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(['expires_in' => 300])]);

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->input('code', '123')
        ->press('verify')
        ->assertSee('The code field must be 6 digits.')
        ->assertNoNavigation();
});

it('surfaces throttling from the API', function () {
    Http::fake(['*/api/v1/auth/otp/request' => Http::response(status: 429)]);

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->assertSee("You've tried that too many times.")
        ->assertSet('step', 'phone');
});

it('lets the customer go back and change their number', function () {
    Http::fake(['*' => Http::response(['expires_in' => 300])]);

    Native::test(Otp::class)
        ->input('phone', '+15551234567')
        ->press('sendCode')
        ->assertSet('step', 'code')
        ->press('changePhone')
        ->assertSet('step', 'phone')
        ->assertSet('code', '');
});
