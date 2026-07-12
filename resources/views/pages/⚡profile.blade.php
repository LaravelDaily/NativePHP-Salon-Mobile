<?php

use App\Services\CustomerAuth;
use Livewire\Component;

new class extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $user = null;

    public function mount(CustomerAuth $auth): void
    {
        $this->user = $auth->user();
    }

    public function signOut(CustomerAuth $auth)
    {
        $auth->signOut();

        return $this->redirectRoute('auth.welcome', navigate: true);
    }

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
        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-accent text-2xl font-semibold text-accent-ink">
            {{ Str::of($user['name'] ?? '?')->substr(0, 1)->upper() }}
        </div>
        <p class="mt-4 text-xl font-semibold" data-testid="profile-name">{{ $user['name'] ?? '' }}</p>
        <p class="mt-1 text-sm text-content-muted">{{ $user['email'] ?? ($user['phone'] ?? '') }}</p>
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

    <button
        type="button"
        wire:click="signOut"
        class="mt-8 w-full rounded-full border border-hairline px-6 py-4 font-medium text-danger"
    >
        Sign Out
    </button>
</div>
