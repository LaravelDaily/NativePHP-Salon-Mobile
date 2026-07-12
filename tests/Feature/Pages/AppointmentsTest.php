<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Native\Mobile\Facades\Device;

beforeEach(function (): void {
    signIn();
});

/**
 * @return array<string, mixed>
 */
function fakeAppointment(int $id, string $service, string $specialist, string $status = 'confirmed'): array
{
    return [
        'id' => $id,
        'status' => $status,
        'status_label' => ucfirst($status),
        'starts_at' => '2026-08-05T10:00:00+00:00',
        'ends_at' => '2026-08-05T10:45:00+00:00',
        'date_label' => 'Wed, 5 Aug 2026',
        'time_label' => '10:00 AM',
        'notes' => null,
        'service' => ['id' => 1, 'name' => $service],
        'specialist' => ['id' => 1, 'name' => $specialist, 'photo_url' => null],
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $upcoming
 * @param  array<int, array<string, mixed>>  $past
 */
function fakeAppointmentsApi(array $upcoming = [], array $past = [], mixed $cancel = null): void
{
    Http::fake([
        '*/api/v1/appointments?filter=upcoming*' => Http::response(['data' => $upcoming]),
        '*/api/v1/appointments?filter=past*' => Http::response(['data' => $past]),
        '*/api/v1/appointments/*' => $cancel ?? Http::response(['data' => fakeAppointment(1, 'Cut', 'Clay Burke', 'cancelled')]),
    ]);
}

it('lists upcoming appointments and history', function () {
    fakeAppointmentsApi(
        upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')],
        past: [fakeAppointment(2, 'Hair Color', 'Eileen Rose', 'completed')],
    );

    Livewire::test('pages::appointments')
        ->assertOk()
        ->assertSee('Upcoming Appointments')
        ->assertSee("Men's Precision Cut")
        ->assertSee('with Clay Burke')
        ->assertSee('Wed, 5 Aug 2026')
        ->assertSee('Reschedule Appointment')
        ->assertSee('Cancel Appointment')
        ->assertSee('History')
        ->assertSee('Hair Color')
        ->assertSee('Completed');
});

it('offers a booking link when there is nothing coming up', function () {
    fakeAppointmentsApi();

    Livewire::test('pages::appointments')
        ->assertSee('You have no upcoming appointments.')
        ->assertSee('Your past visits will appear here.');
});

it('asks for confirmation before cancelling', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Livewire::test('pages::appointments')
        ->assertDontSee('Cancel this appointment?')
        ->call('confirmCancel', 1)
        ->assertSet('cancelling', 1)
        ->assertSee('Cancel this appointment?');

    // Nothing has been cancelled just by asking.
    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('keeps the appointment when the confirmation is declined', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('keepIt')
        ->assertSet('cancelling', null)
        ->assertDontSee('Cancel this appointment?');

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('cancels the appointment once confirmed', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Device::spy();

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('cancel')
        ->assertSet('cancelling', null)
        ->assertSee('Your appointment has been cancelled.');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), 'appointments/1'));

    Device::shouldHaveReceived('vibrate')->once();
});

it('cancels when the native dialog\'s cancel button comes back', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('onDialogButton', 1, 'Cancel appointment', 'cancel-appointment')
        ->assertSet('cancelling', null);

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('keeps the appointment when the native dialog is dismissed', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('onDialogButton', 0, 'Keep it', 'cancel-appointment')
        ->assertSet('cancelling', null);

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('ignores a button press from some other dialog', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('onDialogButton', 1, 'Delete', 'some-other-dialog')
        ->assertSet('cancelling', 1);

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('reads out the salon\'s reason when a cancellation is refused', function () {
    fakeAppointmentsApi(
        upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')],
        cancel: Http::response([
            'message' => 'Appointments can no longer be changed less than 2 hours before they start. Please call the salon.',
            'errors' => ['status' => ['Appointments can no longer be changed less than 2 hours before they start. Please call the salon.']],
        ], 422),
    );

    Livewire::test('pages::appointments')
        ->call('confirmCancel', 1)
        ->call('cancel')
        ->assertSee('less than 2 hours before they start')
        ->assertSet('cancelling', null);
});

it('surfaces a failure to load rather than blowing up', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Livewire::test('pages::appointments')
        ->assertOk()
        ->assertSee('StyleHub is having trouble right now.');
});
