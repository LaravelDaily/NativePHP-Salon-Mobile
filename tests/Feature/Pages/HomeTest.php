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

it('renders the salon fetched from the API', function () {
    Http::fake(['*/api/v1/salon' => Http::response(salonPayload())]);

    Livewire::test('pages::home')
        ->assertOk()
        ->assertSee('StyleHub')
        ->assertSee('Precision cuts and classic grooming')
        ->assertSee('Mon–Fri 09:00–19:00, Sat 10:00–16:00, Sun Closed', escape: false)
        ->assertSee('128 Barber Street, Brooklyn, NY 11201')
        ->assertSet('error', null);
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
