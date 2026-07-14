<?php

use App\Contracts\TokenStore;
use App\NativeComponents\Profile;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

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

    $component = Native::visit('/profile')
        ->assertScreen(Profile::class)
        ->press('toggleEditing')
        ->input('name', 'Jane Doe')
        ->press('save')
        ->assertSee('Profile updated.');

    expect($component->get('user')['name'])->toBe('Jane Doe');

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH' && $request['name'] === 'Jane Doe');
});

it('requests a verification code before changing phone', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000000']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
        '*/api/v1/me/phone/request' => Http::response(['message' => 'Sent']),
    ]);

    Native::test(Profile::class)
        ->press('toggleEditing')
        ->input('phone', '+15550000001')
        ->press('requestPhoneChange')
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

    $component = Native::test(Profile::class)
        ->set('phone', '+15550000001')
        ->set('phoneCode', '123456')
        ->call('verifyPhoneChange')
        ->assertSee('Phone number verified.');

    expect($component->get('user')['phone'])->toBe('+15550000001');
});

it('signs out and lands on the welcome screen', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000000']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
        '*/api/v1/auth/logout' => Http::response(['message' => 'ok']),
    ]);

    Native::test(Profile::class)
        ->press('signOut')
        ->assertReplacedWith('/welcome');

    expect(app(TokenStore::class)->has())->toBeFalse();
});

it('links out to stylists, history and settings', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => null, 'phone' => '+15550000000']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
    ]);

    Native::test(Profile::class)->press('goToStylists')->assertNavigatedTo('/profile/stylists');
    Native::test(Profile::class)->press('goToAppointments')->assertNavigatedTo('/appointments');
    Native::test(Profile::class)->press('goToSettings')->assertNavigatedTo('/settings');
});
