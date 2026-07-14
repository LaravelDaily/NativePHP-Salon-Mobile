<?php

use App\NativeComponents\Appointments;
use App\NativeComponents\Book;
use App\NativeComponents\Profile;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

/*
 * Neither of these screens is a placeholder any more — they are covered in depth
 * by AppointmentsTest and BookTest. What is left here is a smoke test that each
 * tab renders, and both now call the API on mount, so it has to be faked or the
 * screen reaches for the real one.
 */

beforeEach(function (): void {
    signIn();
});

it('renders the appointments screen', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    Native::test(Appointments::class)
        ->assertSee('Upcoming Appointments')
        ->assertSee('You have no upcoming appointments.');
});

it('renders the booking screen', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    Native::test(Book::class)
        ->assertSee('New Appointment');
});

it('renders the profile screen with the menu from the mockups', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    Native::test(Profile::class)
        ->assertSee('My Stylists')
        ->assertSee('Booking History')
        ->assertSee('Salon Feedback')
        ->assertSee('App Settings');
});

it('serves each tab route with its screen', function (string $route, string $screen) {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe']]),
        // Unmatched URLs would otherwise be sent for real, and the booking screen
        // fetches its catalog on mount.
        '*' => Http::response(['data' => []]),
    ]);

    // The HTTP entry answers with a stub for native routes under test;
    // the native harness mounts the same route to prove the screen renders.
    $this->withSession(customerSession())
        ->get(route($route))
        ->assertOk();

    Native::visit(route($route, absolute: false))->assertScreen($screen);
})->with([
    ['appointments', Appointments::class],
    ['book', Book::class],
    ['profile', Profile::class],
]);
