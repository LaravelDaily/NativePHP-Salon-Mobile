<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Component;

new class extends Component
{
    /** @var array<int, array{id: int, name: string, title: string|null, photo_url: string|null, average_rating: float|int|null, feedback_count: int, is_favorite: bool, ...}> */
    public array $stylists = [];

    public ?string $error = null;

    public ?string $notice = null;

    public function mount(ApiClient $api): void
    {
        $this->load($api);
    }

    public function load(ApiClient $api): void
    {
        $this->error = null;

        try {
            /** @var array<int, array{id: int, name: string, title: string|null, photo_url: string|null, average_rating: float|int|null, feedback_count: int, is_favorite: bool, ...}> $stylists */
            $stylists = $api->get('me/stylists')['data'] ?? [];
            $this->stylists = $stylists;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function toggleFavorite(int $specialistId, ApiClient $api): void
    {
        $this->error = null;

        $stylist = collect($this->stylists)->firstWhere('id', $specialistId);

        if ($stylist === null) {
            return;
        }

        try {
            $api->delete("specialists/{$specialistId}/favorite");
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->stylists = array_values(array_filter(
            $this->stylists,
            fn (array $entry): bool => $entry['id'] !== $specialistId,
        ));
        $this->notice = "{$stylist['name']} was removed from My Stylists.";
    }
};
?>

<div class="screen">
    <native:top-bar title="My Stylists" :show-navigation-icon="true" />

    <h2 class="text-2xl font-semibold tracking-tight">Your favorites</h2>
    <p class="mt-2 text-sm text-content-muted">Keep the people who know your style close at hand.</p>

    @if ($error)
        <p class="mt-4 rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger">{{ $error }}</p>
    @endif

    @if ($notice)
        <p class="mt-4 rounded-2xl border border-hairline px-4 py-3 text-sm text-content-muted">{{ $notice }}</p>
    @endif

    <div class="mt-6 grid gap-3">
        @forelse ($stylists as $stylist)
            <article class="card flex items-center gap-4 p-4" wire:key="favorite-{{ $stylist['id'] }}">
                <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-raised font-semibold">
                    @if ($stylist['photo_url'])
                        <img src="{{ $stylist['photo_url'] }}" alt="" class="size-full object-cover">
                    @else
                        {{ Str::of($stylist['name'])->explode(' ')->take(2)->map(fn (string $part): string => Str::substr($part, 0, 1))->implode('') }}
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="truncate font-semibold">{{ $stylist['name'] }}</h3>
                    <p class="truncate text-sm text-content-muted">{{ $stylist['title'] ?? 'StyleHub specialist' }}</p>
                    @if ($stylist['average_rating'] !== null)
                        <p class="mt-1 text-sm text-accent">★ {{ $stylist['average_rating'] }} <span class="text-content-subtle">({{ $stylist['feedback_count'] }})</span></p>
                    @endif
                </div>

                <button
                    type="button"
                    wire:click="toggleFavorite({{ $stylist['id'] }})"
                    wire:loading.attr="disabled"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xl text-accent"
                    aria-label="Remove {{ $stylist['name'] }} from favorites"
                >
                    ♥
                </button>
            </article>
        @empty
            @unless ($error)
                <div class="card p-8 text-center">
                    <p class="text-content-muted">You have not favorited a stylist yet.</p>
                    <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6">Find your stylist</a>
                </div>
            @endunless
        @endforelse
    </div>
</div>
