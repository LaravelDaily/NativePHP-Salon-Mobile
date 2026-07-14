<?php

use App\NativeComponents\Appointments;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Device;
use Native\Mobile\Testing\Native;

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
        'feedback' => null,
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

    Native::visit('/appointments')
        ->assertScreen(Appointments::class)
        ->assertNavTitle('Appointments')
        ->assertSee('Upcoming Appointments')
        ->assertSee("Men's Precision Cut")
        ->assertSee('with Clay Burke')
        ->assertSee('Wed, 5 Aug 2026')
        ->assertSee('Reschedule Appointment')
        ->assertSee('Cancel Appointment')
        ->assertSee('History')
        ->assertSee('Hair Color')
        ->assertSee('Completed')
        ->assertSee('Rate this visit');
});

it('shows an existing rating instead of asking again', function () {
    $appointment = fakeAppointment(2, 'Hair Color', 'Eileen Rose', 'completed');
    $appointment['feedback'] = ['id' => 4, 'rating' => 4, 'comment' => null];
    fakeAppointmentsApi(past: [$appointment]);

    Native::test(Appointments::class)
        ->assertSee('★★★★')
        ->assertDontSee('Rate this visit');
});

it('offers a booking link when there is nothing coming up', function () {
    fakeAppointmentsApi();

    Native::test(Appointments::class)
        ->assertSee('You have no upcoming appointments.')
        ->assertSee('Your past visits will appear here.');
});

it('asks the native dialog for confirmation before cancelling', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Native::test(Appointments::class)
        ->press('confirmCancel(1)')
        ->assertSet('cancelling', 1)
        ->assertNativeCalled('Dialog.Alert');

    // Nothing has been cancelled just by asking.
    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('cancels when the native dialog\'s cancel button comes back', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Device::spy();

    Native::test(Appointments::class)
        ->press('confirmCancel(1)')
        ->emitNative(ButtonPressed::class, ['index' => 1, 'label' => 'Cancel appointment', 'id' => 'cancel-appointment'])
        ->assertSet('cancelling', null)
        ->assertSee('Your appointment has been cancelled.');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), 'appointments/1'));

    Device::shouldHaveReceived('vibrate')->once();
});

it('keeps the appointment when the native dialog is dismissed', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Native::test(Appointments::class)
        ->press('confirmCancel(1)')
        ->emitNative(ButtonPressed::class, ['index' => 0, 'label' => 'Keep it', 'id' => 'cancel-appointment'])
        ->assertSet('cancelling', null);

    Http::assertNotSent(fn ($request): bool => $request->method() === 'DELETE');
});

it('ignores a button press from some other dialog', function () {
    fakeAppointmentsApi(upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')]);

    Native::test(Appointments::class)
        ->press('confirmCancel(1)')
        ->emitNative(ButtonPressed::class, ['index' => 1, 'label' => 'Delete', 'id' => 'some-other-dialog'])
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

    Native::test(Appointments::class)
        ->press('confirmCancel(1)')
        ->emitNative(ButtonPressed::class, ['index' => 1, 'label' => 'Cancel appointment', 'id' => 'cancel-appointment'])
        ->assertSee('less than 2 hours before they start')
        ->assertSet('cancelling', null);
});

it('navigates to reschedule and feedback from the lists', function () {
    fakeAppointmentsApi(
        upcoming: [fakeAppointment(1, "Men's Precision Cut", 'Clay Burke')],
        past: [fakeAppointment(2, 'Hair Color', 'Eileen Rose', 'completed')],
    );

    Native::test(Appointments::class)
        ->press('reschedule(1)')
        ->assertNavigatedTo('/appointments/1/reschedule');

    Native::test(Appointments::class)
        ->press('rateVisit(2)')
        ->assertNavigatedTo('/appointments/2/feedback');
});

it('surfaces a failure to load rather than blowing up', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Native::test(Appointments::class)
        ->assertSee('StyleHub is having trouble right now.');
});
