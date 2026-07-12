<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Livewire\Component;

new class extends Component
{
    /** @var array{id: int, name: string, email?: string|null, phone?: string|null, ...}|null */
    public ?array $user = null;

    /** @var array<int, array{id: int, name: string, photo_url: string|null, ...}> */
    public array $favoriteStylists = [];

    /** @var array{average_rating: float|int|null, feedback_count: int, ...}|null */
    public ?array $salon = null;

    public function mount(CustomerAuth $auth, ApiClient $api): void
    {
        $this->user = $auth->user();

        $this->loadFavoriteStylists($api);
        $this->loadSalon($api);
    }

    public function loadFavoriteStylists(ApiClient $api): void
    {
        try {
            /** @var array<int, array{id: int, name: string, photo_url: string|null, ...}> $favorites */
            $favorites = $api->get('me/stylists')['data'] ?? [];
            $this->favoriteStylists = $favorites;
        } catch (ApiException) {
            $this->favoriteStylists = [];
        }
    }

    public function loadSalon(ApiClient $api): void
    {
        try {
            /** @var array{average_rating: float|int|null, feedback_count: int, ...}|null $salon */
            $salon = $api->get('salon')['data'] ?? null;
            $this->salon = $salon;
        } catch (ApiException) {
            $this->salon = null;
        }
    }

    public function signOut(CustomerAuth $auth): mixed
    {
        $auth->signOut();

        return $this->redirectRoute('auth.welcome', navigate: true);
    }
};
?>

<div class="screen">
    <native:top-bar title="Profile" :show-navigation-icon="false" />

    <div class="card mt-2 p-8 text-center">
        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-accent text-2xl font-semibold text-accent-ink">
            {{ Str::of($user['name'] ?? '?')->substr(0, 1)->upper() }}
        </div>
        <p class="mt-4 text-xl font-semibold" data-testid="profile-name">{{ $user['name'] ?? '' }}</p>
        <p class="mt-1 text-sm text-content-muted">{{ $user['email'] ?? ($user['phone'] ?? '') }}</p>
    </div>

    <div class="mt-6 space-y-3">
        <a href="{{ route('profile.stylists') }}" wire:navigate class="card flex items-center justify-between gap-4 p-5">
            <div class="min-w-0 flex-1">
                <p class="font-medium">My Stylists</p>
                <p class="text-sm text-content-muted">
                    {{ $favoriteStylists === [] ? 'Your favourite specialists' : count($favoriteStylists).' saved' }}
                </p>
            </div>

            @if ($favoriteStylists !== [])
                <div class="flex -space-x-3" aria-label="Favorite stylist avatars">
                    @foreach (array_slice($favoriteStylists, 0, 3) as $stylist)
                        <span class="flex size-9 items-center justify-center overflow-hidden rounded-full border-2 border-surface bg-surface-raised text-xs font-semibold" wire:key="profile-stylist-{{ $stylist['id'] }}">
                            @if ($stylist['photo_url'])
                                <img src="{{ $stylist['photo_url'] }}" alt="" class="size-full object-cover">
                            @else
                                {{ Str::substr($stylist['name'], 0, 1) }}
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif

            <span class="text-content-subtle">&rsaquo;</span>
        </a>

        <a href="{{ route('appointments') }}" wire:navigate class="card flex items-center justify-between p-5">
            <div>
                <p class="font-medium">Booking History</p>
                <p class="text-sm text-content-muted">Past visits and ratings</p>
            </div>
            <span class="text-content-subtle">&rsaquo;</span>
        </a>

        <div class="card flex items-center justify-between gap-4 p-5">
            <div>
                <p class="font-medium">Salon Feedback</p>
                <p class="text-sm text-content-muted">Based on {{ $salon['feedback_count'] ?? 0 }} customer reviews</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold text-accent">★ {{ $salon['average_rating'] ?? '—' }}</p>
                <p class="text-xs text-content-subtle">out of 5</p>
            </div>
        </div>

        <div class="card flex items-center justify-between p-5">
            <div>
                <p class="font-medium">App Settings</p>
                <p class="text-sm text-content-muted">Notifications and account</p>
            </div>
            <span class="text-content-subtle">&rsaquo;</span>
        </div>
    </div>

    <button
        type="button"
        wire:click="signOut"
        class="mt-8 w-full rounded-full border border-hairline px-6 py-4 font-medium text-danger"
    >
        Sign Out
    </button>
</div>
