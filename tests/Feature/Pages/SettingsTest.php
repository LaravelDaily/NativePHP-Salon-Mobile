<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    signIn();
});

it('persists deferred notification preferences', function () {
    Http::fake(fn ($request) => Http::response(['data' => [
        'notification_preferences' => ['booking_confirmed' => $request->method() === 'PATCH' ? false : true],
    ]]));

    Livewire::test('pages::settings')
        ->set('preferences.booking_confirmed', false)
        ->call('savePreferences')
        ->assertSee('Preferences saved')
        ->assertSet('preferences.booking_confirmed', false);

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH' && $request['notification_preferences']['booking_confirmed'] === false);
});

it('requires confirmation before deleting an account', function () {
    Http::fake(['*/api/v1/me' => Http::response(['data' => ['notification_preferences' => []]])]);

    Livewire::test('pages::settings')
        ->call('deleteAccount')
        ->assertSet('confirmingDeletion', true)
        ->assertSee('Tap again');

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('deletes the account after confirmation and returns to welcome', function () {
    Http::fake(['*' => Http::response(['data' => ['notification_preferences' => []]])]);

    Livewire::test('pages::settings')
        ->call('deleteAccount')
        ->call('deleteAccount')
        ->assertRedirect(route('auth.welcome'));

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE' && str_ends_with($request->url(), '/api/v1/me'));
});
