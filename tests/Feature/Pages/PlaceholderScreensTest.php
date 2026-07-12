<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders the appointments screen', function () {
    Livewire::test('pages::appointments')
        ->assertOk()
        ->assertSee('Upcoming Appointments')
        ->assertSee('You have no upcoming appointments.');
});

it('renders the booking screen', function () {
    Livewire::test('pages::book')
        ->assertOk()
        ->assertSee('New Appointment');
});

it('renders the profile screen with the menu from the mockups', function () {
    Livewire::test('pages::profile')
        ->assertOk()
        ->assertSee('My Stylists')
        ->assertSee('Booking History')
        ->assertSee('Salon Feedback')
        ->assertSee('App Settings');
});

it('serves each tab route with its component', function (string $route, string $component) {
    Http::fake(['*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe']])]);

    $this->withSession(customerSession())
        ->get(route($route))
        ->assertOk()
        ->assertSeeLivewire($component);
})->with([
    ['appointments', 'pages::appointments'],
    ['book', 'pages::book'],
    ['profile', 'pages::profile'],
]);
