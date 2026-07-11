<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="screen">
    <native:top-bar title="Appointments" :show-navigation-icon="false" />

    <h2 class="text-2xl font-semibold tracking-tight">Upcoming Appointments</h2>

    <div class="card mt-6 p-8 text-center">
        <p class="text-content-muted">You have no upcoming appointments.</p>
        <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6">Book an appointment</a>
    </div>

    <h2 class="mt-10 text-2xl font-semibold tracking-tight">History</h2>

    <div class="card mt-6 p-8 text-center">
        <p class="text-content-muted">Your past visits will appear here.</p>
    </div>
</div>
