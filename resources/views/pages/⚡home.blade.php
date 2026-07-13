<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Component;

new class extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $salon = null;

    /** @var array<string, mixed>|null */
    public ?array $next = null;

    /** @var array<string, mixed>|null */
    public ?array $pendingFeedback = null;

    public ?string $error = null;

    public function mount(ApiClient $api): void
    {
        $this->load($api);
    }

    public function load(ApiClient $api): void
    {
        $this->error = null;

        $responses = $api->getMany([
            'salon' => ['path' => 'salon'],
            'upcoming' => ['path' => 'appointments', 'query' => ['filter' => 'upcoming', 'per_page' => 1]],
            'reviewable' => ['path' => 'appointments', 'query' => ['filter' => 'reviewable', 'per_page' => 1]],
        ]);

        if ($responses['salon']['error'] instanceof ApiException) {
            $this->salon = null;
            $this->error = $responses['salon']['error']->getMessage();

            return;
        }

        $this->salon = $responses['salon']['data']['data'] ?? null;
        $this->next = $responses['upcoming']['data']['data'][0] ?? null;
        $this->pendingFeedback = $responses['reviewable']['data']['data'][0] ?? null;
    }
};
?>

<div class="screen">
    @if ($error)
        <div class="card mt-6 p-6 text-center">
            <p class="text-content-muted">{{ $error }}</p>
            <button type="button" wire:click="load" class="btn-accent mt-5">Try again</button>
        </div>
    @else
        <p class="text-sm text-content-muted">Welcome to</p>
        <h1 class="mt-1 text-4xl font-semibold tracking-tight" data-testid="salon-name">
            {{ $salon['name'] ?? '' }}
        </h1>

        @if (! empty($salon['tagline']))
            <p class="mt-2 text-content-muted">{{ $salon['tagline'] }}</p>
        @endif

        <div class="card mt-8 p-6">
            <p class="text-xs uppercase tracking-widest text-content-subtle">Opening hours</p>
            <p class="mt-2 leading-relaxed">{{ $salon['opening_summary'] ?? '' }}</p>

            @if (! empty($salon['address']['formatted']))
                <p class="mt-4 text-sm text-content-muted">{{ $salon['address']['formatted'] }}</p>
            @endif
        </div>

        <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6">Book an appointment</a>

        @if ($pendingFeedback)
            <a href="{{ route('appointments.feedback', $pendingFeedback['id']) }}" wire:navigate class="card mt-6 block border-accent/40 p-5" data-testid="feedback-prompt">
                <p class="text-xs uppercase tracking-widest text-accent">How did we do?</p>
                <p class="mt-2 font-semibold">Rate your {{ $pendingFeedback['service']['name'] }}</p>
                <p class="mt-1 text-sm text-content-muted">Tap to leave feedback for your visit with {{ $pendingFeedback['specialist']['name'] }}.</p>
            </a>
        @endif

        <div class="card mt-6 p-6" data-testid="next-appointment">
            <p class="text-xs uppercase tracking-widest text-content-subtle">Next appointment</p>

            @if ($next)
                <p class="mt-2 text-lg font-semibold">{{ $next['service']['name'] }}</p>
                <p class="text-sm text-content-muted">with {{ $next['specialist']['name'] }}</p>
                <p class="mt-2">{{ $next['date_label'] }} · {{ $next['time_label'] }}</p>

                <a href="{{ route('appointments') }}" wire:navigate class="mt-4 inline-block text-sm text-accent">
                    View appointments
                </a>
            @else
                <p class="mt-2 text-content-muted">Your next visit will show up here.</p>
            @endif
        </div>
    @endif
</div>
