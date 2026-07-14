<?php

use App\NativeComponents\Feedback;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    signIn();
});

function reviewableAppointment(): array
{
    return [
        'id' => 7,
        'status' => 'completed',
        'feedback' => null,
        'service' => ['name' => "Men's Precision Cut"],
        'specialist' => ['name' => 'Clay Burke'],
    ];
}

it('renders the rating form for a completed appointment', function () {
    Http::fake(['*/api/v1/appointments/7' => Http::response(['data' => reviewableAppointment()])]);

    Native::visit('/appointments/7/feedback')
        ->assertScreen(Feedback::class)
        ->assertNavTitle('Salon Feedback')
        ->assertSee('How was your visit?')
        ->assertSee("Men's Precision Cut")
        ->assertSee('Clay Burke')
        ->assertSet('rating', 0);
});

it('requires a star rating', function () {
    Http::fake(['*/api/v1/appointments/7' => Http::response(['data' => reviewableAppointment()])]);

    Native::visit('/appointments/7/feedback')
        ->press('submit')
        ->assertSee('Choose a rating from one to five stars.');

    Http::assertNotSent(fn ($request): bool => $request->method() === 'POST');
});

it('submits a rating and optional comment', function () {
    Http::fake([
        '*/api/v1/appointments/7' => Http::response(['data' => reviewableAppointment()]),
        '*/api/v1/appointments/7/feedback' => Http::response(['data' => [
            'id' => 3,
            'rating' => 5,
            'comment' => 'Excellent work.',
        ]], 201),
    ]);

    Native::visit('/appointments/7/feedback')
        ->press('rate(5)')
        ->input('comment', 'Excellent work.')
        ->press('submit')
        ->assertSet('submitted', true)
        ->assertSee('Thank you');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request['rating'] === 5
        && $request['comment'] === 'Excellent work.');
});

it('shows API feedback rule errors', function () {
    Http::fake([
        '*/api/v1/appointments/7' => Http::response(['data' => reviewableAppointment()]),
        '*/api/v1/appointments/7/feedback' => Http::response([
            'message' => 'The given data was invalid.',
            'errors' => ['appointment' => ['This appointment has already been reviewed.']],
        ], 422),
    ]);

    Native::visit('/appointments/7/feedback')
        ->press('rate(4)')
        ->press('submit')
        ->assertSet('fieldErrors', ['appointment' => 'This appointment has already been reviewed.'])
        ->assertSee('This appointment has already been reviewed.');
});
