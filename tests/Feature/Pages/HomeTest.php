<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function salonPayload(array $overrides = []): array
{
    return ['data' => [
        'name' => 'StyleHub',
        'tagline' => 'Precision cuts and classic grooming',
        'opening_summary' => 'Mon–Fri 09:00–19:00, Sat 10:00–16:00, Sun Closed',
        'address' => ['formatted' => '128 Barber Street, Brooklyn, NY 11201'],
        ...$overrides,
    ]];
}

/**
 * @param  array<int, array<string, mixed>>  $upcoming
 * @param  array<int, array<string, mixed>>  $reviewable
 */
function fakeHomeApi(array $upcoming = [], array $reviewable = []): void
{
    Http::preventStrayRequests();

    Http::fake([
        '*/api/v1/salon' => Http::response(salonPayload()),
        '*/api/v1/appointments?filter=upcoming*' => Http::response(['data' => $upcoming]),
        '*/api/v1/appointments?filter=reviewable*' => Http::response(['data' => $reviewable]),
    ]);
}

it('renders a home skeleton before requesting dashboard data', function () {
    Http::preventStrayRequests();

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee('home-dashboard-skeleton');

    Http::assertNothingSent();
});

it('loads the dashboard data only when the deferred island is rendered', function () {
    fakeHomeApi();

    $component = Livewire::test('pages::home');
    $dashboard = $component->instance()->dashboard;

    expect($dashboard['salon']['name'])->toBe('StyleHub')
        ->and($dashboard['next'])->toBeNull()
        ->and($dashboard['pendingFeedback'])->toBeNull()
        ->and($dashboard['error'])->toBeNull();

    Http::assertSentCount(3);
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/v1/salon'));
});

it('includes a review prompt and next appointment in deferred dashboard data', function () {
    fakeHomeApi([[
        'id' => 3,
        'status' => 'confirmed',
        'status_label' => 'Confirmed',
        'starts_at' => '2026-08-05T10:00:00+00:00',
        'date_label' => 'Wed, 5 Aug 2026',
        'time_label' => '10:00 AM',
        'service' => ['id' => 1, 'name' => "Men's Precision Cut"],
        'specialist' => ['id' => 1, 'name' => 'Clay Burke'],
    ]], [[
        'id' => 8,
        'status' => 'completed',
        'feedback' => null,
        'service' => ['name' => 'Hair Color'],
        'specialist' => ['name' => 'Eileen Rose'],
    ]]);

    $dashboard = Livewire::test('pages::home')->instance()->dashboard;

    expect($dashboard['next']['id'])->toBe(3)
        ->and($dashboard['pendingFeedback']['id'])->toBe(8);
});

it('returns a friendly error for unavailable salon data', function () {
    Http::preventStrayRequests();
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $dashboard = Livewire::test('pages::home')->instance()->dashboard;

    expect($dashboard['salon'])->toBeNull()
        ->and($dashboard['error'])->toBe("Can't reach StyleHub right now. Check your connection and try again.");
});

it('is reachable at the home route by a signed-in customer', function () {
    Http::preventStrayRequests();

    $this->withSession(customerSession())
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::home')
        ->assertSee('home-dashboard-skeleton');
});
