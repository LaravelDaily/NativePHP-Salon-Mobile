<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    signIn();
});

it('updates the customer profile', function () {
    Http::fake(function ($request) {
        if ($request->method() === 'PATCH') {
            return Http::response(['data' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'john@example.com', 'phone' => '+15550000000']]);
        }

        if (str_ends_with($request->url(), '/me/stylists')) {
            return Http::response(['data' => []]);
        }

        if (str_ends_with($request->url(), '/salon')) {
            return Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]);
        }

        return Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+15550000000']]);
    });

    Livewire::test('pages::profile')
        ->set('editing', true)
        ->set('name', 'Jane Doe')
        ->call('save')
        ->assertSee('Profile updated.')
        ->assertSet('user.name', 'Jane Doe');

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH' && $request['name'] === 'Jane Doe');
});

it('requests a verification code before changing phone', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000000']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
        '*/api/v1/me/phone/request' => Http::response(['message' => 'Sent']),
    ]);

    Livewire::test('pages::profile')
        ->set('editing', true)
        ->set('phone', '+15550000001')
        ->call('requestPhoneChange')
        ->assertSet('phoneVerificationSent', true)
        ->assertSee('Verification code');
});

it('updates the phone after the verification code succeeds', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000000']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
        '*/api/v1/me/phone/verify' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000001']]),
    ]);

    Livewire::test('pages::profile')
        ->set('phone', '+15550000001')
        ->set('phoneCode', '123456')
        ->call('verifyPhoneChange')
        ->assertSet('user.phone', '+15550000001')
        ->assertSee('Phone number verified.');
});
