<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Component;

new class extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $salon = null;

    /** The soonest appointment still to come, if any. */
    public ?array $next = null;

    public ?string $error = null;

    public function mount(ApiClient $api): void
    {
        $this->loadSalon($api);
        $this->loadNextAppointment($api);
    }

    public function loadSalon(ApiClient $api): void
    {
        $this->error = null;

        try {
            /** @var array<string, mixed>|null $salon */
            $salon = $api->get('salon')['data'] ?? null;
            $this->salon = $salon;
        } catch (ApiException $exception) {
            $this->salon = null;
            $this->error = $exception->getMessage();
        }
    }

    /**
     * The API returns upcoming appointments soonest first, so the next one is
     * simply the first. A failure here is not worth shouting about: the customer
     * came to the home screen, not to be told the network is unhappy.
     */
    public function loadNextAppointment(ApiClient $api): void
    {
        try {
            /** @var array<int, array<string, mixed>> $upcoming */
            $upcoming = $api->get('appointments', ['filter' => 'upcoming', 'per_page' => 1])['data'] ?? [];

            $this->next = $upcoming[0] ?? null;
        } catch (ApiException) {
            $this->next = null;
        }
    }
};
?>

<div class="screen">
    @if ($error)
        <div class="card mt-6 p-6 text-center">
            <p class="text-content-muted">{{ $error }}</p>
            <button type="button" wire:click="loadSalon" class="btn-accent mt-5">Try again</button>
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
