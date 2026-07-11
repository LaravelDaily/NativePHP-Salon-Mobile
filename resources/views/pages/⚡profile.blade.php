<?php

use Livewire\Component;

new class extends Component
{
    /**
     * The profile menu from the mockups. Each row is wired up in a later phase.
     *
     * @return array<int, array{label: string, description: string}>
     */
    public function menu(): array
    {
        return [
            ['label' => 'My Stylists', 'description' => 'Your favourite specialists'],
            ['label' => 'Booking History', 'description' => 'Past visits and reorders'],
            ['label' => 'Salon Feedback', 'description' => 'Rate your experience'],
            ['label' => 'App Settings', 'description' => 'Notifications and account'],
        ];
    }
};
?>

<div class="screen">
    <native:top-bar title="Profile" :show-navigation-icon="false" />

    <div class="card mt-2 p-8 text-center">
        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-surface-raised text-2xl font-semibold text-content-muted">
            &mdash;
        </div>
        <p class="mt-4 text-xl font-semibold">Not signed in</p>
        <p class="mt-1 text-sm text-content-muted">Sign in to manage your appointments.</p>
    </div>

    <div class="mt-6 space-y-3">
        @foreach ($this->menu() as $item)
            <div class="card flex items-center justify-between p-5">
                <div>
                    <p class="font-medium">{{ $item['label'] }}</p>
                    <p class="text-sm text-content-muted">{{ $item['description'] }}</p>
                </div>
                <span class="text-content-subtle">&rsaquo;</span>
            </div>
        @endforeach
    </div>
</div>
