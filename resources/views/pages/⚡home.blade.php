<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * @return array{
     *     error: string|null,
     *     next: array{id: int, service: array{name: string, ...}, specialist: array{name: string, ...}, date_label: string, time_label: string, ...}|null,
     *     pendingFeedback: array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}|null,
     *     salon: array{name: string, tagline?: string|null, opening_summary: string, address: array{formatted?: string|null, ...}, ...}|null
     * }
     */
    #[Computed]
    public function dashboard(): array
    {
        $api = app(ApiClient::class);

        $responses = $api->getMany([
            'salon' => ['path' => 'salon'],
            'upcoming' => ['path' => 'appointments', 'query' => ['filter' => 'upcoming', 'per_page' => 1]],
            'reviewable' => ['path' => 'appointments', 'query' => ['filter' => 'reviewable', 'per_page' => 1]],
        ]);

        if ($responses['salon']['error'] instanceof ApiException) {
            $salon = null;
            $pendingFeedback = null;
            $next = null;
            $error = $responses['salon']['error']->getMessage();
        } else {
            /** @var array{name: string, tagline?: string|null, opening_summary: string, address: array{formatted?: string|null, ...}, ...}|null $salon */
            $salon = $responses['salon']['data']['data'] ?? null;

            /** @var array<int, array{id: int, service: array{name: string, ...}, specialist: array{name: string, ...}, date_label: string, time_label: string, ...}> $upcoming */
            $upcoming = $responses['upcoming']['data']['data'] ?? [];
            $next = $upcoming[0] ?? null;

            /** @var array<int, array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}> $reviewable */
            $reviewable = $responses['reviewable']['data']['data'] ?? [];
            $pendingFeedback = $reviewable[0] ?? null;
            $error = null;
        }

        return compact('error', 'next', 'pendingFeedback', 'salon');
    }
};
?>

<div class="screen">
    @island(name: 'home-dashboard', defer: true)
        @placeholder
            <div class="animate-pulse" data-testid="home-dashboard-skeleton">
                <div class="h-4 w-24 rounded bg-hairline"></div>
                <div class="mt-3 h-10 w-48 rounded bg-hairline"></div>
                <div class="mt-8 h-40 rounded-3xl bg-hairline"></div>
                <div class="mt-6 h-32 rounded-3xl bg-hairline"></div>
            </div>
        @endplaceholder

        @php($dashboard = $this->dashboard)

        @if ($dashboard['error'])
            <div class="card mt-6 p-6 text-center">
                <p class="text-content-muted">{{ $dashboard['error'] }}</p>
                <button type="button" wire:click="$refresh" class="btn-accent mt-5">Try again</button>
            </div>
        @else
            <p class="text-sm text-content-muted">Welcome to</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight" data-testid="salon-name">
                {{ $dashboard['salon']['name'] ?? '' }}
            </h1>

            @if (! empty($dashboard['salon']['tagline']))
                <p class="mt-2 text-content-muted">{{ $dashboard['salon']['tagline'] }}</p>
            @endif

            <div class="card mt-8 p-6">
                <p class="text-xs uppercase tracking-widest text-content-subtle">Opening hours</p>
                <p class="mt-2 leading-relaxed">{{ $dashboard['salon']['opening_summary'] ?? '' }}</p>

                @if (! empty($dashboard['salon']['address']['formatted']))
                    <p class="mt-4 text-sm text-content-muted">{{ $dashboard['salon']['address']['formatted'] }}</p>
                @endif
            </div>

            <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6">Book an appointment</a>

            @if ($dashboard['pendingFeedback'])
                <a href="{{ route('appointments.feedback', $dashboard['pendingFeedback']['id']) }}" wire:navigate class="card mt-6 block border-accent/40 p-5" data-testid="feedback-prompt">
                    <p class="text-xs uppercase tracking-widest text-accent">How did we do?</p>
                    <p class="mt-2 font-semibold">Rate your {{ $dashboard['pendingFeedback']['service']['name'] }}</p>
                    <p class="mt-1 text-sm text-content-muted">Tap to leave feedback for your visit with {{ $dashboard['pendingFeedback']['specialist']['name'] }}.</p>
                </a>
            @endif

            <div class="card mt-6 p-6" data-testid="next-appointment">
                <p class="text-xs uppercase tracking-widest text-content-subtle">Next appointment</p>

                @if ($dashboard['next'])
                    <p class="mt-2 text-lg font-semibold">{{ $dashboard['next']['service']['name'] }}</p>
                    <p class="text-sm text-content-muted">with {{ $dashboard['next']['specialist']['name'] }}</p>
                    <p class="mt-2">{{ $dashboard['next']['date_label'] }} · {{ $dashboard['next']['time_label'] }}</p>

                    <a href="{{ route('appointments') }}" wire:navigate class="mt-4 inline-block text-sm text-accent">
                        View appointments
                    </a>
                @else
                    <p class="mt-2 text-content-muted">Your next visit will show up here.</p>
                @endif
            </div>
        @endif
    @endisland
</div>
