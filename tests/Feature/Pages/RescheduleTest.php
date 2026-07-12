<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2026-08-03 08:00:00');

    signIn();
});

/**
 * The appointment being moved: a 45-minute cut with Clay at 11:00 on the 3rd.
 *
 * @return array<string, mixed>
 */
function existingAppointment(): array
{
    return [
        'id' => 42,
        'status' => 'confirmed',
        'status_label' => 'Confirmed',
        'starts_at' => CarbonImmutable::parse('2026-08-03 11:00')->toIso8601String(),
        'ends_at' => CarbonImmutable::parse('2026-08-03 11:45')->toIso8601String(),
        'date_label' => 'Mon, 3 Aug 2026',
        'time_label' => '11:00 AM',
        'notes' => null,
        'service' => ['id' => 1, 'name' => "Men's Precision Cut", 'duration_minutes' => 45],
        'specialist' => ['id' => 1, 'name' => 'Clay Burke', 'photo_url' => null],
    ];
}

function fakeRescheduleApi(mixed $patch = null): void
{
    $slots = array_map(fn (string $time): array => [
        'time' => $time,
        'label' => CarbonImmutable::parse("2026-08-03 {$time}")->format('g:i A'),
        'starts_at' => CarbonImmutable::parse("2026-08-03 {$time}")->toIso8601String(),
        'ends_at' => CarbonImmutable::parse("2026-08-03 {$time}")->addMinutes(45)->toIso8601String(),
    ], ['11:00', '14:00']);

    $moved = Http::response(['data' => [
        ...existingAppointment(),
        'starts_at' => CarbonImmutable::parse('2026-08-03 14:00')->toIso8601String(),
        'time_label' => '2:00 PM',
    ]]);

    Http::fake([
        // The same URL serves the appointment being moved (GET) and the move
        // itself (PATCH), so the stub has to tell them apart.
        '*/api/v1/appointments/42' => fn ($request) => $request->method() === 'PATCH'
            ? ($patch ?? $moved)
            : Http::response(['data' => existingAppointment()]),
        '*/api/v1/availability*' => Http::response(['data' => [
            'date' => '2026-08-03',
            'service' => ['id' => 1, 'name' => "Men's Precision Cut", 'duration_minutes' => 45],
            'specialists' => [[
                'specialist' => ['id' => 1, 'name' => 'Clay Burke', 'title' => null, 'bio' => null, 'photo_url' => null],
                'slot_count' => 2,
                'periods' => [
                    ['period' => 'morning', 'label' => 'Morning', 'slots' => [$slots[0]]],
                    ['period' => 'afternoon', 'label' => 'Afternoon', 'slots' => [$slots[1]]],
                ],
            ]],
        ]]),
    ]);
}

it('opens prefilled with the appointment being moved', function () {
    fakeRescheduleApi();

    Livewire::test('pages::book', ['appointment' => '42'])
        ->assertOk()
        ->assertSet('serviceId', 1)
        ->assertSet('specialistId', 1)
        ->assertSet('date', '2026-08-03')
        ->assertSet('startsAt', null)
        ->assertSee('Pick a new time')
        ->assertSee('Rescheduling')
        ->assertSee('Currently Mon, 3 Aug 2026 at 11:00 AM')
        ->assertSee('Confirm New Time')
        // The service is fixed: no catalog to pick from.
        ->assertDontSee('Choose Service');
});

it('asks the API to ignore the appointment being moved', function () {
    fakeRescheduleApi();

    Livewire::test('pages::book', ['appointment' => '42']);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'availability')
        && str_contains($request->url(), 'exclude_appointment_id=42'));
});

it('offers the time the appointment already holds', function () {
    fakeRescheduleApi();

    // 11:00 is its own slot, freed by the exclusion, so it can stay put or move.
    Livewire::test('pages::book', ['appointment' => '42'])
        ->assertSee('11:00 AM')
        ->assertSee('2:00 PM');
});

it('moves the appointment to the chosen time', function () {
    fakeRescheduleApi();

    Livewire::test('pages::book', ['appointment' => '42'])
        ->call('selectTime', CarbonImmutable::parse('2026-08-03 14:00')->toIso8601String())
        ->call('confirm')
        ->assertSee('Appointment moved')
        ->assertSee('2:00 PM')
        // Rescheduling is a one-shot errand: no "book another" here.
        ->assertDontSee('Book another');

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH'
        && str_contains($request->url(), 'appointments/42')
        && $request['starts_at'] === CarbonImmutable::parse('2026-08-03 14:00')->toIso8601String()
        && $request['specialist_id'] === 1);
});

it('explains when the appointment can no longer be moved', function () {
    fakeRescheduleApi(patch: Http::response([
        'message' => 'Appointments can no longer be changed less than 2 hours before they start. Please call the salon.',
        'errors' => ['status' => ['Appointments can no longer be changed less than 2 hours before they start. Please call the salon.']],
    ], 422));

    Livewire::test('pages::book', ['appointment' => '42'])
        ->call('selectTime', CarbonImmutable::parse('2026-08-03 14:00')->toIso8601String())
        ->call('confirm')
        ->assertSet('booked', null)
        ->assertSee('less than 2 hours before they start');
});

it('offers the slots again when the new time was taken first', function () {
    fakeRescheduleApi(patch: Http::response([
        'message' => 'That time is no longer available. Please pick another slot.',
        'errors' => ['starts_at' => ['That time is no longer available. Please pick another slot.']],
    ], 422));

    Livewire::test('pages::book', ['appointment' => '42'])
        ->call('selectTime', CarbonImmutable::parse('2026-08-03 14:00')->toIso8601String())
        ->call('confirm')
        ->assertSet('booked', null)
        ->assertSet('startsAt', null)
        ->assertSee('That time is no longer available. Please pick another slot.')
        ->assertSee('Morning');
});
