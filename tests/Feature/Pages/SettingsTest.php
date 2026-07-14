<?php

use App\NativeComponents\Settings;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    signIn();
});

it('persists deferred notification preferences', function () {
    Http::fake(fn ($request) => Http::response(['data' => [
        'notification_preferences' => ['booking_confirmed' => $request->method() === 'PATCH' ? false : true],
    ]]));

    Native::visit('/settings')
        ->assertScreen(Settings::class)
        ->toggle("setPreference('booking_confirmed')", false)
        ->press('savePreferences')
        ->assertSee('Preferences saved')
        ->assertSet('preferences', ['booking_confirmed' => false]);

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH' && $request['notification_preferences']['booking_confirmed'] === false);
});

it('asks the native dialog before deleting an account', function () {
    Http::fake(['*/api/v1/me' => Http::response(['data' => ['notification_preferences' => []]])]);

    Native::test(Settings::class)
        ->press('deleteAccount')
        ->assertSet('confirmingDeletion', true)
        ->assertNativeCalled('Dialog.Alert');

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('deletes the account after confirmation and returns to welcome', function () {
    Http::fake(['*' => Http::response(['data' => ['notification_preferences' => []]])]);

    Native::test(Settings::class)
        ->press('deleteAccount')
        ->emitNative(ButtonPressed::class, ['index' => 1, 'label' => 'Delete permanently', 'id' => 'delete-account'])
        ->assertReplacedWith('/welcome');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE' && str_ends_with($request->url(), '/api/v1/me'));
});

it('keeps the account when the dialog is declined', function () {
    Http::fake(['*' => Http::response(['data' => ['notification_preferences' => []]])]);

    Native::test(Settings::class)
        ->press('deleteAccount')
        ->emitNative(ButtonPressed::class, ['index' => 0, 'label' => 'Keep my account', 'id' => 'delete-account'])
        ->assertSet('confirmingDeletion', false)
        ->assertNoNavigation();

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('signs out from settings', function () {
    Http::fake(['*' => Http::response(['data' => ['notification_preferences' => []]])]);

    Native::test(Settings::class)
        ->press('signOut')
        ->assertReplacedWith('/welcome');
});
