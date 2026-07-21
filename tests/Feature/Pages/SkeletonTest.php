<?php

use App\NativeComponents\Appointments;
use App\NativeComponents\Book;
use App\NativeComponents\Feedback;
use App\NativeComponents\Home;
use App\NativeComponents\Layouts\SalonStackLayout;
use App\NativeComponents\Layouts\SalonTabsLayout;
use App\NativeComponents\Profile;
use App\NativeComponents\Settings;
use App\NativeComponents\Stylists;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;

/*
 * Every screen that talks to the API on mount is #[Lazy], so the router paints
 * its skeleton before mount() starts waiting. These tests pin the ordering: a
 * skeleton that arrives after the data has landed is no skeleton at all.
 */

beforeEach(function (): void {
    signIn();
});

/**
 * A single appointment, for the two screens opened on one — enough of it for
 * both the reschedule prefill and the feedback header.
 *
 * @return array<string, mixed>
 */
function skeletonAppointment(): array
{
    return [
        'id' => 42,
        'status' => 'completed',
        'starts_at' => '2026-08-03T11:00:00+00:00',
        'date_label' => 'Mon, 3 Aug 2026',
        'time_label' => '11:00 AM',
        'feedback' => null,
        'service' => ['id' => 1, 'name' => "Men's Precision Cut"],
        'specialist' => ['id' => 1, 'name' => 'Clay Burke'],
    ];
}

it('paints its skeleton before the first API call', function (string $screen, array $params, string $layout) {
    $publishedWhenApiWasCalled = null;

    Http::fake(function ($request) use (&$publishedWhenApiWasCalled) {
        $publishedWhenApiWasCalled ??= count(FakeBridge::current()->publishes);

        // List endpoints answer empty; the two screens opened on one
        // appointment need that appointment back or they cannot mount.
        return Http::response(['data' => str_contains($request->url(), 'appointments/42')
            ? skeletonAppointment()
            : []]);
    });

    Native::test($screen, $params, layout: $layout);

    expect($publishedWhenApiWasCalled)
        ->not->toBeNull('The screen never called the API, so there was nothing to paint a skeleton for.')
        ->toBe(1, 'The skeleton — and only the skeleton — should be on screen when the request goes out.');
})->with([
    'home' => [Home::class, [], SalonTabsLayout::class],
    'appointments' => [Appointments::class, [], SalonTabsLayout::class],
    'book' => [Book::class, [], SalonTabsLayout::class],
    'profile' => [Profile::class, [], SalonTabsLayout::class],
    'stylists' => [Stylists::class, [], SalonStackLayout::class],
    'settings' => [Settings::class, [], SalonStackLayout::class],
    'reschedule' => [Book::class, ['appointment' => 42], SalonStackLayout::class],
    'feedback' => [Feedback::class, ['appointment' => 42], SalonStackLayout::class],
]);

it('shows the tab bar on the skeleton, not just once the data lands', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    Native::test(Home::class, layout: SalonTabsLayout::class);

    expect(json_encode(FakeBridge::current()->publishes[0]))->toContain('Appointments');
});

it('titles the reschedule skeleton from the route, before the appointment is fetched', function () {
    Http::fake(['*' => Http::response(['data' => skeletonAppointment()])]);

    Native::test(Book::class, ['appointment' => 42], layout: SalonStackLayout::class)
        ->assertNavTitle('Reschedule');
});
