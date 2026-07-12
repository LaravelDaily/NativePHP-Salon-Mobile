<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use App\Support\NativeShell;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Dialog;

new class extends Component
{
    private const DELETE_DIALOG = 'delete-account';

    /** @var array<string, bool> */
    public array $preferences = [];

    public bool $confirmingDeletion = false;

    public ?string $notice = null;

    public ?string $error = null;

    public function mount(CustomerAuth $auth): void
    {
        $user = $auth->user();
        $this->preferences = $user['notification_preferences'] ?? [];
    }

    public function savePreferences(ApiClient $api): void
    {
        try {
            /** @var array<string, bool> $preferences */
            $preferences = $api->patch('me', ['notification_preferences' => $this->preferences])['data']['notification_preferences'];
            $this->preferences = $preferences;
            $this->notice = 'Preferences saved. They will take effect when notifications are enabled in a later release.';
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function deleteAccount(ApiClient $api, CustomerAuth $auth): mixed
    {
        if (! $this->confirmingDeletion) {
            $this->confirmingDeletion = true;

            if (NativeShell::isRunning()) {
                Dialog::alert(
                    'Delete account?',
                    'This permanently removes your appointments and signs out every device.',
                    ['Keep my account', 'Delete permanently'],
                )->id(self::DELETE_DIALOG)->show();
            }

            return null;
        }

        try {
            $api->delete('me');
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return null;
        }

        $auth->signOut();

        return $this->redirectRoute('auth.welcome', navigate: true);
    }

    #[OnNative(ButtonPressed::class)]
    public function onDialogButton(int $index, string $label, ?string $id = null): mixed
    {
        if ($id !== self::DELETE_DIALOG) {
            return null;
        }

        if ($index === 1) {
            return $this->deleteAccount(app(ApiClient::class), app(CustomerAuth::class));
        }

        $this->confirmingDeletion = false;

        return null;
    }

    public function cancelDeletion(): void
    {
        $this->confirmingDeletion = false;
    }

    public function signOut(CustomerAuth $auth): mixed
    {
        $auth->signOut();

        return $this->redirectRoute('auth.welcome', navigate: true);
    }
};
?>

<div class="screen">
    <native:top-bar title="App Settings" :show-navigation-icon="true" />

    <h2 class="text-2xl font-semibold tracking-tight">Preferences</h2>
    <p class="mt-2 text-sm text-content-muted">Saved now; notification delivery remains deferred until Phase 8 is enabled.</p>

    <form wire:submit="savePreferences" class="card mt-6 divide-y divide-hairline">
        @foreach (['booking_confirmed' => 'Booking confirmations', 'appointment_reminder' => 'Appointment reminders', 'appointment_rescheduled' => 'Reschedules', 'appointment_cancelled' => 'Cancellations'] as $key => $label)
            <label class="flex items-center justify-between gap-4 p-5" wire:key="preference-{{ $key }}">
                <span>{{ $label }}</span>
                <input type="checkbox" wire:model="preferences.{{ $key }}" class="size-5 accent-accent">
            </label>
        @endforeach
        <div class="p-5"><button type="submit" class="btn-accent">Save preferences</button></div>
    </form>

    @if ($notice)
        <p class="mt-4 rounded-2xl border border-hairline px-4 py-3 text-sm text-content-muted">{{ $notice }}</p>
    @endif
    @if ($error)
        <p class="mt-4 rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger">{{ $error }}</p>
    @endif

    <section class="card mt-10 border-danger/40 p-5">
        <h3 class="font-semibold text-danger">Delete account</h3>
        <p class="mt-2 text-sm text-content-muted">This permanently removes your account, appointments, and sign-in tokens.</p>
        @if ($confirmingDeletion)
            <p class="mt-4 text-sm text-danger">This cannot be undone. Tap again to permanently delete your account.</p>
            <div class="mt-4 flex gap-3">
                <button type="button" wire:click="deleteAccount" class="flex-1 rounded-control bg-danger px-6 py-3 font-semibold text-content">Delete permanently</button>
                <button type="button" wire:click="cancelDeletion" class="flex-1 rounded-control border border-hairline px-6 py-3 font-semibold">Cancel</button>
            </div>
        @else
            <button type="button" wire:click="deleteAccount" class="mt-4 w-full rounded-control border border-danger/50 px-6 py-3 font-semibold text-danger">Delete account</button>
        @endif
    </section>

    <button type="button" wire:click="signOut" class="mt-8 w-full rounded-control border border-hairline px-6 py-4 font-medium text-danger">Sign Out</button>
</div>
