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

    public bool $editing = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $phoneCode = '';

    public bool $phoneVerificationSent = false;

    public ?string $notice = null;

    public ?string $error = null;

    public function mount(CustomerAuth $auth, ApiClient $api): void
    {
        $this->user = $auth->user();
        $this->name = $this->user['name'] ?? '';
        $this->email = $this->user['email'] ?? '';
        $this->phone = $this->user['phone'] ?? '';

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

    public function save(ApiClient $api): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            /** @var array{id: int, name: string, email: string|null, phone: string|null, ...} $user */
            $user = $api->patch('me', $validated)['data'];
            $this->user = $user;
            $this->email = $user['email'] ?? '';
            $this->editing = false;
            $this->notice = 'Profile updated.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
        }
    }

    public function requestPhoneChange(ApiClient $api): void
    {
        $this->validate(['phone' => ['required', 'string']]);

        try {
            $api->post('me/phone/request', ['phone' => $this->phone]);
            $this->phoneVerificationSent = true;
            $this->notice = 'We sent a verification code to your new phone.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
        }
    }

    public function verifyPhoneChange(ApiClient $api): void
    {
        $this->validate(['phoneCode' => ['required', 'digits:6']]);

        try {
            /** @var array{id: int, name: string, email: string|null, phone: string|null, ...} $user */
            $user = $api->post('me/phone/verify', ['phone' => $this->phone, 'code' => $this->phoneCode])['data'];
            $this->user = $user;
            $this->phone = $user['phone'] ?? '';
            $this->phoneCode = '';
            $this->phoneVerificationSent = false;
            $this->notice = 'Phone number verified.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
        }
    }

    private function addApiErrors(ApiException $exception): void
    {
        foreach ($exception->errors as $field => $messages) {
            $this->addError($field === 'code' ? 'phoneCode' : $field, $messages[0]);
        }

        if ($exception->errors === []) {
            $this->error = $exception->getMessage();
        }
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
        <button type="button" wire:click="$toggle('editing')" class="mt-4 text-sm font-medium text-accent">Edit Profile</button>
    </div>

    @if ($editing)
        <form wire:submit="save" class="card mt-4 space-y-4 p-5">
            <label class="block text-sm font-medium">Name
                <input wire:model="name" class="mt-2 w-full rounded-control border border-hairline bg-surface-raised px-4 py-3 text-content" />
                @error('name') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium">Email
                <input type="email" wire:model="email" class="mt-2 w-full rounded-control border border-hairline bg-surface-raised px-4 py-3 text-content" />
                @error('email') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <button type="submit" wire:loading.attr="disabled" class="btn-accent">Save profile</button>
        </form>

        <div class="card mt-4 space-y-4 p-5">
            <label class="block text-sm font-medium">Phone number
                <input type="tel" wire:model="phone" class="mt-2 w-full rounded-control border border-hairline bg-surface-raised px-4 py-3 text-content" />
                @error('phone') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            @if ($phoneVerificationSent)
                <label class="block text-sm font-medium">Verification code
                    <input inputmode="numeric" wire:model="phoneCode" class="mt-2 w-full rounded-control border border-hairline bg-surface-raised px-4 py-3 text-content" />
                    @error('phoneCode') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
                </label>
                <button type="button" wire:click="verifyPhoneChange" class="btn-accent">Verify new phone</button>
            @else
                <button type="button" wire:click="requestPhoneChange" class="btn-accent">Verify new phone</button>
            @endif
        </div>
    @endif

    @if ($notice)
        <p class="mt-4 rounded-2xl border border-hairline px-4 py-3 text-sm text-content-muted">{{ $notice }}</p>
    @endif
    @if ($error)
        <p class="mt-4 rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger">{{ $error }}</p>
    @endif

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

        <a href="{{ route('settings') }}" wire:navigate class="card flex items-center justify-between p-5">
            <div>
                <p class="font-medium">App Settings</p>
                <p class="text-sm text-content-muted">Notifications and account</p>
            </div>
            <span class="text-content-subtle">&rsaquo;</span>
        </a>
    </div>

    <button
        type="button"
        wire:click="signOut"
        class="mt-8 w-full rounded-full border border-hairline px-6 py-4 font-medium text-danger"
    >
        Sign Out
    </button>
</div>
