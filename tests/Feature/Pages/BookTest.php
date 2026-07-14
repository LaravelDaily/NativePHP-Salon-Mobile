<?php

use App\NativeComponents\Book;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;
use Native\Mobile\Testing\Native;

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
function fakeSpecialist(int $id, string $name, array $times, bool $isFavorite = false): array
{
    $slots = array_map(fn (string $time): array => [
        'time' => $time,
        'label' => CarbonImmutable::parse("2026-08-03 {$time}")->format('g:i A'),
        'starts_at' => CarbonImmutable::parse("2026-08-03 {$time}")->toIso8601String(),
        'ends_at' => CarbonImmutable::parse("2026-08-03 {$time}")->addMinutes(45)->toIso8601String(),
    ], $times);

    return [
        'specialist' => ['id' => $id, 'name' => $name, 'title' => 'Barber', 'bio' => null, 'photo_url' => null, 'is_favorite' => $isFavorite],
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
        '*/api/v1/specialists/*/favorite' => Http::response(['data' => []]),
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

    Native::visit('/book')
        ->assertScreen(Book::class)
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

    Native::test(Book::class)
        ->call('confirm')
        ->assertSet('booked', null);

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'appointments'));
});

it('re-fetches availability when the service changes and forgets the chosen time', function () {
    fakeApi();

    Native::test(Book::class)
        ->press("selectTime('".slotAt('09:00')."')")
        ->assertSet('startsAt', slotAt('09:00'))
        ->press('selectService(2)')
        ->assertSet('serviceId', 2)
        ->assertSet('startsAt', null);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'availability')
        && str_contains($request->url(), 'service_id=2'));
});

it('re-fetches availability when the date changes', function () {
    fakeApi();

    Native::test(Book::class)
        ->press("selectDate('2026-08-05')")
        ->assertSet('date', '2026-08-05')
        ->assertSet('startsAt', null);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'date=2026-08-05'));
});

it('switches specialist without asking the API again', function () {
    fakeApi([
        fakeSpecialist(1, 'Clay Burke', ['09:00']),
        fakeSpecialist(2, 'Eileen Rose', ['14:00']),
    ]);

    $component = Native::test(Book::class)
        ->assertSet('specialistId', 1)
        ->assertSee('9:00 AM');

    $requestsBefore = count(Http::recorded());

    $component->press('selectSpecialist(2)')
        ->assertSet('specialistId', 2)
        ->assertSet('startsAt', null)
        ->assertSee('2:00 PM')
        ->assertDontSee('9:00 AM');

    expect(Http::recorded())->toHaveCount($requestsBefore);
});

it('toggles a specialist favorite and moves favorites first', function () {
    fakeApi([
        fakeSpecialist(1, 'Clay Burke', ['09:00']),
        fakeSpecialist(2, 'Eileen Rose', ['14:00']),
    ]);

    $component = Native::test(Book::class)->press('toggleFavorite(2)');

    expect($component->get('availability')[0]['specialist'])
        ->id->toBe(2)
        ->is_favorite->toBeTrue();

    $component->press('toggleFavorite(2)');

    expect($component->get('availability')[1]['specialist']['is_favorite'])->toBeFalse();

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_contains($request->url(), 'specialists/2/favorite'));
    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), 'specialists/2/favorite'));
});

it('keeps a favorite specialist first when availability loads', function () {
    fakeApi([
        fakeSpecialist(2, 'Eileen Rose', ['14:00'], true),
        fakeSpecialist(1, 'Clay Burke', ['09:00']),
    ]);

    Native::test(Book::class)
        ->assertSet('specialistId', 2)
        ->assertSee('Eileen Rose')
        ->assertSee('Clay Burke');
});

it('skips a specialist who is fully booked and picks one who is free', function () {
    fakeApi([
        fakeSpecialist(1, 'Clay Burke', []),
        fakeSpecialist(2, 'Eileen Rose', ['14:00']),
    ]);

    Native::test(Book::class)
        ->assertSet('specialistId', 2)
        ->assertSee('2:00 PM');
});

it('says so when nobody has a free slot that day', function () {
    fakeApi([fakeSpecialist(1, 'Clay Burke', [])]);

    Native::test(Book::class)
        ->assertSet('specialistId', null)
        ->assertSee('No times available on this day');
});

it('books the chosen slot and shows the confirmation', function () {
    fakeApi();

    Dialog::spy();
    Device::spy();

    Native::test(Book::class)
        ->press("selectTime('".slotAt('09:00')."')")
        ->press('confirm')
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

    Native::test(Book::class)
        ->press("selectTime('".slotAt('09:00')."')")
        ->press('confirm')
        ->assertSet('booked', null)
        // The slot is dropped and the times reloaded, so the customer can retry.
        ->assertSet('startsAt', null)
        ->assertSee('That time is no longer available. Please pick another slot.')
        ->assertSee('Morning');
});

it('surfaces an unreachable API rather than blowing up', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Native::test(Book::class)
        ->assertSee('StyleHub is having trouble right now.');
});

it('pages the date strip a week at a time and never before today', function () {
    fakeApi();

    Native::test(Book::class)
        ->assertSee('August')
        ->press('previousWeek')
        ->assertSet('weekOffset', 0)
        ->press('nextWeek')
        ->assertSet('weekOffset', 1)
        ->assertSee('10')
        ->press('previousWeek')
        ->assertSet('weekOffset', 0);
});

it('lets the customer start another booking from the confirmation', function () {
    fakeApi();

    Native::test(Book::class)
        ->press("selectTime('".slotAt('09:00')."')")
        ->press('confirm')
        ->assertSee('Booking confirmed')
        ->press('bookAnother')
        ->assertSet('booked', null)
        ->assertSet('startsAt', null)
        ->assertSee('Choose Service');
});
