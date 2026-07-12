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
 * The home screen asks for the salon and for the next appointment, so both have
 * to be stubbed or the unmatched one is sent for real.
 *
 * @param  array<int, array<string, mixed>>  $upcoming
 * @param  array<int, array<string, mixed>>  $past
 */
function fakeHomeApi(array $upcoming = [], array $past = []): void
{
    Http::fake([
        '*/api/v1/salon' => Http::response(salonPayload()),
        '*/api/v1/appointments?filter=upcoming*' => Http::response(['data' => $upcoming]),
        '*/api/v1/appointments?filter=reviewable*' => Http::response(['data' => $past]),
    ]);
}

it('renders the salon fetched from the API', function () {
    fakeHomeApi();

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee('StyleHub')
        ->assertSee('Precision cuts and classic grooming')
        ->assertSee('Mon–Fri 09:00–19:00, Sat 10:00–16:00, Sun Closed', escape: false)
        ->assertSee('128 Barber Street, Brooklyn, NY 11201')
        ->assertSet('error', null);
});

it('prompts for feedback after a completed visit', function () {
    fakeHomeApi(past: [[
        'id' => 8,
        'status' => 'completed',
        'feedback' => null,
        'service' => ['name' => 'Hair Color'],
        'specialist' => ['name' => 'Eileen Rose'],
    ]]);

    Livewire::test('pages::home')
        ->assertSet('pendingFeedback.id', 8)
        ->assertSee('How did we do?')
        ->assertSee('Rate your Hair Color')
        ->assertSee('Eileen Rose');
});

it('does not prompt when the API has no reviewable appointment', function () {
    fakeHomeApi();

    Livewire::test('pages::home')
        ->assertSet('pendingFeedback', null)
        ->assertDontSee('How did we do?');
});

it('shows the next appointment on the dashboard', function () {
    fakeHomeApi([[
        'id' => 3,
        'status' => 'confirmed',
        'status_label' => 'Confirmed',
        'starts_at' => '2026-08-05T10:00:00+00:00',
        'date_label' => 'Wed, 5 Aug 2026',
        'time_label' => '10:00 AM',
        'service' => ['id' => 1, 'name' => "Men's Precision Cut"],
        'specialist' => ['id' => 1, 'name' => 'Clay Burke'],
    ]]);

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSet('next.id', 3)
        ->assertSee('Next appointment')
        ->assertSee("Men's Precision Cut")
        ->assertSee('with Clay Burke')
        ->assertSee('Wed, 5 Aug 2026')
        ->assertSee('View appointments');

    // The soonest is simply the first the API returns.
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'filter=upcoming'));
});

it('invites the customer to book when nothing is coming up', function () {
    fakeHomeApi();

    Livewire::test('pages::home')
        ->assertSet('next', null)
        ->assertSee('Your next visit will show up here.')
        ->assertSee('Book an appointment');
});

it('calls the salon endpoint on the configured API host', function () {
    Http::fake(['*' => Http::response(salonPayload())]);

    Livewire::test('pages::home')->assertOk();

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/v1/salon'));
});

it('shows a friendly message when the API cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee("Can't reach StyleHub right now.")
        ->assertSee('Try again')
        ->assertSet('salon', null);
});

it('shows a friendly message when the API errors', function () {
    Http::fake(['*' => Http::response(status: 500)]);

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee('StyleHub is having trouble right now.');
});

it('recovers when the retry succeeds', function () {
    $offline = true;

    Http::fake(function () use (&$offline) {
        if ($offline) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(salonPayload(['name' => 'StyleHub Brooklyn']));
    });

    $component = Livewire::test('pages::home')->assertSee('Try again');

    $offline = false;

    $component->call('loadSalon')
        ->assertSet('error', null)
        ->assertSee('StyleHub Brooklyn');
});

it('is reachable at the home route by a signed-in customer', function () {
    Http::fake(['*' => Http::response(salonPayload())]);

    $this->withSession(customerSession())
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::home');
});
