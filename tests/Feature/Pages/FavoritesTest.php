<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    signIn();
});

function favoriteStylist(int $id = 1, string $name = 'Clay Burke'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'title' => 'Senior Barber',
        'photo_url' => null,
        'average_rating' => 4.8,
        'feedback_count' => 24,
        'is_favorite' => true,
    ];
}

it('lists favorite stylists', function () {
    Http::fake(['*/api/v1/me/stylists' => Http::response(['data' => [favoriteStylist()]])]);

    Livewire::test('pages::stylists')
        ->assertOk()
        ->assertSee('Your favorites')
        ->assertSee('Clay Burke')
        ->assertSee('Senior Barber')
        ->assertSee('4.8');
});

it('removes a stylist from favorites', function () {
    Http::fake([
        '*/api/v1/me/stylists' => Http::response(['data' => [favoriteStylist()]]),
        '*/api/v1/specialists/1/favorite' => Http::response(status: 204),
    ]);

    Livewire::test('pages::stylists')
        ->call('toggleFavorite', 1)
        ->assertSet('stylists', [])
        ->assertSee('Clay Burke was removed from My Stylists.')
        ->assertSee('You have not favorited a stylist yet.');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('shows favorite avatars and salon rating on profile', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']]),
        '*/api/v1/me/stylists' => Http::response(['data' => [favoriteStylist()]]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => 4.7, 'feedback_count' => 86]]),
    ]);

    Livewire::test('pages::profile')
        ->assertSee('John Doe')
        ->assertSee('1 saved')
        ->assertSee('4.7')
        ->assertSee('86 customer reviews');
});
