<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Component;

new class extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $salon = null;

    public ?string $error = null;

    public function mount(ApiClient $api): void
    {
        $this->loadSalon($api);
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

        <div class="card mt-6 p-6">
            <p class="text-xs uppercase tracking-widest text-content-subtle">Next appointment</p>
            <p class="mt-2 text-content-muted">Your next visit will show up here.</p>
        </div>
    @endif
</div>
