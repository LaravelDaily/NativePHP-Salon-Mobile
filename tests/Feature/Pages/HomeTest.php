<?php

use App\NativeComponents\Home;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

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

    Native::test(Home::class)
        ->assertSee('StyleHub')
        ->assertSee('Precision cuts and classic grooming')
        ->assertSet('error', null);

    Http::assertSentCount(3);
});

it('shows the shared tab bar on the home route', function () {
    fakeHomeApi();

    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertHasTabBar();
});

it('prompts for feedback after a completed visit', function () {
    fakeHomeApi(reviewable: [[
        'id' => 8,
        'status' => 'completed',
        'feedback' => null,
        'service' => ['name' => 'Hair Color'],
        'specialist' => ['name' => 'Eileen Rose'],
    ]]);

    Native::test(Home::class)
        ->assertSee('HOW DID WE DO?')
        ->press('giveFeedback')
        ->assertNavigatedTo('/appointments/8/feedback');
});

it('shows the next appointment on the dashboard', function () {
    fakeHomeApi([[
        'id' => 3,
        'date_label' => 'Wed, 5 Aug 2026',
        'time_label' => '10:00 AM',
        'service' => ['name' => "Men's Precision Cut"],
        'specialist' => ['name' => 'Clay Burke'],
    ]]);

    Native::test(Home::class)
        ->assertSee("Men's Precision Cut")
        ->assertSee('with Clay Burke')
        ->press('viewAppointments')
        ->assertNavigatedTo('/appointments');
});

it('shows a friendly message when the salon API cannot be reached', function () {
    Http::preventStrayRequests();
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    Native::test(Home::class)
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

    $component = Native::test(Home::class)->assertSee('Try again');
    $offline = false;

    $component->press('load')
        ->assertSet('error', null)
        ->assertSee('StyleHub Brooklyn');
});

it('is reachable at the home route by a signed-in customer', function () {
    fakeHomeApi();

    $this->withSession(customerSession())
        ->get(route('home'))
        ->assertOk();
});
