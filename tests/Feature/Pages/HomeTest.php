<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** @return array<string, mixed> */
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

/** @param array<int, array<string, mixed>> $upcoming @param array<int, array<string, mixed>> $reviewable */
function fakeHomeApi(array $upcoming = [], array $reviewable = []): void
{
    Http::preventStrayRequests();
    Http::fake([
        '*/api/v1/salon' => Http::response(salonPayload()),
        '*/api/v1/appointments?filter=upcoming*' => Http::response(['data' => $upcoming]),
        '*/api/v1/appointments?filter=reviewable*' => Http::response(['data' => $reviewable]),
    ]);
}

it('renders the dashboard after concurrent API loading', function () {
    fakeHomeApi();

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee('StyleHub')
        ->assertSee('Precision cuts and classic grooming')
        ->assertSet('error', null);

    Http::assertSentCount(3);
});

it('prompts for feedback after a completed visit', function () {
    fakeHomeApi(reviewable: [[
        'id' => 8,
        'status' => 'completed',
        'feedback' => null,
        'service' => ['name' => 'Hair Color'],
        'specialist' => ['name' => 'Eileen Rose'],
    ]]);

    Livewire::test('pages::home')
        ->assertSet('pendingFeedback.id', 8)
        ->assertSee('How did we do?');
});

it('shows the next appointment on the dashboard', function () {
    fakeHomeApi([[
        'id' => 3,
        'date_label' => 'Wed, 5 Aug 2026',
        'time_label' => '10:00 AM',
        'service' => ['name' => "Men's Precision Cut"],
        'specialist' => ['name' => 'Clay Burke'],
    ]]);

    Livewire::test('pages::home')
        ->assertSet('next.id', 3)
        ->assertSee("Men's Precision Cut")
        ->assertSee('with Clay Burke');
});

it('shows a friendly message when the salon API cannot be reached', function () {
    Http::preventStrayRequests();
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    Livewire::test('pages::home')
        ->assertSee("Can't reach StyleHub right now.")
        ->assertSet('salon', null);
});

it('recovers when a retry succeeds', function () {
    $offline = true;
    Http::preventStrayRequests();
    Http::fake(function () use (&$offline) {
        if ($offline) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(salonPayload(['name' => 'StyleHub Brooklyn']));
    });

    $component = Livewire::test('pages::home')->assertSee('Try again');
    $offline = false;

    $component->call('load')
        ->assertSet('error', null)
        ->assertSee('StyleHub Brooklyn');
});

it('is reachable at the home route by a signed-in customer', function () {
    fakeHomeApi();

    $this->withSession(customerSession())
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::home');
});
