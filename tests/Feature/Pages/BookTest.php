<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;

beforeEach(function (): void {
    $this->travelTo('2026-08-03 08:00:00');

    signIn();
});

/**
 * @return array<int, array<string, mixed>>
 */
function fakeServices(): array
{
    return [
        ['id' => 1, 'name' => "Men's Precision Cut", 'icon' => 'scissors', 'description' => null,
            'duration_minutes' => 45, 'price_cents' => 3500, 'price_formatted' => '$35.00', 'specialist_ids' => [1, 2]],
        ['id' => 2, 'name' => 'Hair Color', 'icon' => 'palette', 'description' => null,
            'duration_minutes' => 90, 'price_cents' => 8500, 'price_formatted' => '$85.00', 'specialist_ids' => [2]],
    ];
}

/**
 * @param  array<int, string>  $times  Slot start times, as 'HH:MM' on the booking date.
 * @return array<string, mixed>
 */
function fakeSpecialist(int $id, string $name, array $times): array
{
    $slots = array_map(fn (string $time): array => [
        'time' => $time,
        'label' => CarbonImmutable::parse("2026-08-03 {$time}")->format('g:i A'),
        'starts_at' => CarbonImmutable::parse("2026-08-03 {$time}")->toIso8601String(),
        'ends_at' => CarbonImmutable::parse("2026-08-03 {$time}")->addMinutes(45)->toIso8601String(),
    ], $times);

    return [
        'specialist' => ['id' => $id, 'name' => $name, 'title' => 'Barber', 'bio' => null, 'photo_url' => null],
        'slot_count' => count($slots),
        'periods' => $slots === [] ? [] : [
            ['period' => 'morning', 'label' => 'Morning', 'slots' => $slots],
        ],
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $specialists
 */
function fakeApi(array $specialists = [], mixed $booking = null): void
{
    Http::fake([
        '*/api/v1/services' => Http::response(['data' => fakeServices()]),
        '*/api/v1/availability*' => Http::response(['data' => [
            'date' => '2026-08-03',
            'service' => ['id' => 1, 'name' => "Men's Precision Cut", 'duration_minutes' => 45],
            'specialists' => $specialists === [] ? [fakeSpecialist(1, 'Clay Burke', ['09:00', '09:30'])] : $specialists,
        ]]),
        '*/api/v1/appointments' => $booking ?? Http::response(['data' => [
            'id' => 7,
            'status' => 'confirmed',
            'starts_at' => CarbonImmutable::parse('2026-08-03 09:00')->toIso8601String(),
            'date_label' => 'Mon, 3 Aug 2026',
            'time_label' => '9:00 AM',
            'service' => ['id' => 1, 'name' => "Men's Precision Cut"],
            'specialist' => ['id' => 1, 'name' => 'Clay Burke'],
        ]], 201),
    ]);
}

function slotAt(string $time): string
{
    return CarbonImmutable::parse("2026-08-03 {$time}")->toIso8601String();
}

it('opens with the first service selected and its times listed', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->assertOk()
        ->assertSet('serviceId', 1)
        ->assertSet('specialistId', 1)
        ->assertSet('startsAt', null)
        ->assertSee("Men's Precision Cut")
        ->assertSee('$35.00')
        ->assertSee('Morning')
        ->assertSee('9:00 AM')
        ->assertSee('Clay Burke');
});

it('cannot confirm until a time is chosen', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->call('confirm')
        ->assertSet('booked', null);

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'appointments'));
});

it('re-fetches availability when the service changes and forgets the chosen time', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->call('selectTime', slotAt('09:00'))
        ->assertSet('startsAt', slotAt('09:00'))
        ->call('selectService', 2)
        ->assertSet('serviceId', 2)
        ->assertSet('startsAt', null);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'availability')
        && str_contains($request->url(), 'service_id=2'));
});

it('re-fetches availability when the date changes', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->call('selectDate', '2026-08-05')
        ->assertSet('date', '2026-08-05')
        ->assertSet('startsAt', null);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'date=2026-08-05'));
});

it('switches specialist without asking the API again', function () {
    fakeApi([
        fakeSpecialist(1, 'Clay Burke', ['09:00']),
        fakeSpecialist(2, 'Eileen Rose', ['14:00']),
    ]);

    $component = Livewire::test('pages::book')
        ->assertSet('specialistId', 1)
        ->assertSee('9:00 AM');

    $requestsBefore = count(Http::recorded());

    $component->call('selectSpecialist', 2)
        ->assertSet('specialistId', 2)
        ->assertSet('startsAt', null)
        ->assertSee('2:00 PM')
        ->assertDontSee('9:00 AM');

    expect(Http::recorded())->toHaveCount($requestsBefore);
});

it('skips a specialist who is fully booked and picks one who is free', function () {
    fakeApi([
        fakeSpecialist(1, 'Clay Burke', []),
        fakeSpecialist(2, 'Eileen Rose', ['14:00']),
    ]);

    Livewire::test('pages::book')
        ->assertSet('specialistId', 2)
        ->assertSee('2:00 PM');
});

it('says so when nobody has a free slot that day', function () {
    fakeApi([fakeSpecialist(1, 'Clay Burke', [])]);

    Livewire::test('pages::book')
        ->assertSet('specialistId', null)
        ->assertSee('No times available on this day');
});

it('books the chosen slot and shows the confirmation', function () {
    fakeApi();

    Dialog::spy();
    Device::spy();

    Livewire::test('pages::book')
        ->call('selectTime', slotAt('09:00'))
        ->call('confirm')
        ->assertSet('booked.id', 7)
        ->assertSee('Booking confirmed')
        ->assertSee('Clay Burke')
        ->assertSee('9:00 AM');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'appointments')
        && $request['service_id'] === 1
        && $request['specialist_id'] === 1
        && $request['starts_at'] === slotAt('09:00'));

    Dialog::shouldHaveReceived('toast')->once();
    Device::shouldHaveReceived('vibrate')->once();
});

it('offers the slots again when the chosen one was taken first', function () {
    fakeApi(booking: Http::response([
        'message' => 'That time is no longer available. Please pick another slot.',
        'errors' => ['starts_at' => ['That time is no longer available. Please pick another slot.']],
    ], 422));

    Livewire::test('pages::book')
        ->call('selectTime', slotAt('09:00'))
        ->call('confirm')
        ->assertSet('booked', null)
        // The slot is dropped and the times reloaded, so the customer can retry.
        ->assertSet('startsAt', null)
        ->assertSee('That time is no longer available. Please pick another slot.')
        ->assertSee('Morning');
});

it('surfaces an unreachable API rather than blowing up', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Livewire::test('pages::book')
        ->assertOk()
        ->assertSee('StyleHub is having trouble right now.');
});

it('pages the date strip a week at a time and never before today', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->assertSee('August')
        ->call('previousWeek')
        ->assertSet('weekOffset', 0)
        ->call('nextWeek')
        ->assertSet('weekOffset', 1)
        ->assertSee('10')
        ->call('previousWeek')
        ->assertSet('weekOffset', 0);
});

it('lets the customer start another booking from the confirmation', function () {
    fakeApi();

    Livewire::test('pages::book')
        ->call('selectTime', slotAt('09:00'))
        ->call('confirm')
        ->assertSee('Booking confirmed')
        ->call('bookAnother')
        ->assertSet('booked', null)
        ->assertSet('startsAt', null)
        ->assertSee('Choose Service');
});
