<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

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

    Livewire::test('pages::feedback', ['appointment' => '7'])
        ->assertOk()
        ->assertSee('How was your visit?')
        ->assertSee("Men's Precision Cut")
        ->assertSee('Clay Burke')
        ->assertSet('rating', 0);
});

it('requires a star rating', function () {
    Http::fake(['*/api/v1/appointments/7' => Http::response(['data' => reviewableAppointment()])]);

    Livewire::test('pages::feedback', ['appointment' => '7'])
        ->call('submit')
        ->assertHasErrors('rating');

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

    Livewire::test('pages::feedback', ['appointment' => '7'])
        ->set('rating', 5)
        ->set('comment', 'Excellent work.')
        ->call('submit')
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

    Livewire::test('pages::feedback', ['appointment' => '7'])
        ->set('rating', 4)
        ->call('submit')
        ->assertHasErrors('appointment')
        ->assertSee('This appointment has already been reviewed.');
});
