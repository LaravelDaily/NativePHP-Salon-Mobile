<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use App\Support\NativeShell;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;

new class extends Component
{
    /** Correlates the native dialog's button press back to this screen. */
    private const CANCEL_DIALOG = 'cancel-appointment';

    /** @var array<int, array<string, mixed>> */
    public array $upcoming = [];

    /** @var array<int, array<string, mixed>> */
    public array $past = [];

    /** The appointment awaiting a yes/no on cancellation. */
    public ?int $cancelling = null;

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
            /** @var array<int, array<string, mixed>> $upcoming */
            $upcoming = $api->get('appointments', ['filter' => 'upcoming'])['data'] ?? [];

            /** @var array<int, array<string, mixed>> $past */
            $past = $api->get('appointments', ['filter' => 'past', 'per_page' => 5])['data'] ?? [];

            $this->upcoming = $upcoming;
            $this->past = $past;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    /**
     * Cancelling is destructive and cannot be undone, so it is always confirmed.
     * On a device that confirmation is a native dialog; in a browser — where
     * `Dialog::alert()` has no bridge to call — the screen asks inline instead.
     */
    public function confirmCancel(int $appointmentId): void
    {
        $this->cancelling = $appointmentId;
        $this->notice = null;

        if (! $this->isNative) {
            return;
        }

        Dialog::alert(
            'Cancel appointment?',
            'This frees the slot for someone else. You can always book again.',
            ['Keep it', 'Cancel appointment'],
        )->id(self::CANCEL_DIALOG)->show();
    }

    /**
     * The native dialog's answer. Button 0 is "Keep it".
     */
    #[OnNative(ButtonPressed::class)]
    public function onDialogButton(int $index, string $label, ?string $id = null): void
    {
        if ($id !== self::CANCEL_DIALOG) {
            return;
        }

        $index === 1 ? $this->cancel(app(ApiClient::class)) : $this->keepIt();
    }

    public function keepIt(): void
    {
        $this->cancelling = null;
    }

    public function cancel(ApiClient $api): void
    {
        if ($this->cancelling === null) {
            return;
        }

        $this->error = null;

        try {
            $api->delete("appointments/{$this->cancelling}");
        } catch (ApiException $exception) {
            // The salon's rules — too close to the start, already cancelled —
            // come back as validation errors worth reading out.
            $this->error = $exception->errors['status'][0] ?? $exception->getMessage();
            $this->cancelling = null;

            return;
        }

        $this->cancelling = null;
        $this->notice = 'Your appointment has been cancelled.';

        Device::vibrate();

        $this->load($api);
    }

    #[Computed]
    public function isNative(): bool
    {
        return NativeShell::isRunning();
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function cancellingAppointment(): ?array
    {
        return collect($this->upcoming)->firstWhere('id', $this->cancelling);
    }
};
?>

<div class="screen">
    <native:top-bar title="Appointments" :show-navigation-icon="false" />

    <h2 class="text-2xl font-semibold tracking-tight">Upcoming Appointments</h2>

    @if ($error)
        <p class="mt-4 rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger" data-testid="appointments-error">
            {{ $error }}
        </p>
    @endif

    @if ($notice)
        <p class="mt-4 rounded-2xl border border-hairline px-4 py-3 text-sm text-content-muted" data-testid="appointments-notice">
            {{ $notice }}
        </p>
    @endif

    {{-- In-app stand-in for the native confirm dialog, used off-device. --}}
    @if ($cancelling && ! $this->isNative && $this->cancellingAppointment)
        <div class="card mt-4 border-danger/40 p-5" data-testid="cancel-confirm">
            <p class="font-medium">Cancel this appointment?</p>
            <p class="mt-1 text-sm text-content-muted">
                {{ $this->cancellingAppointment['service']['name'] }} on
                {{ $this->cancellingAppointment['date_label'] }}. This frees the slot for someone else.
            </p>

            <div class="mt-4 flex gap-3">
                <button type="button" wire:click="cancel" class="btn-accent flex-1 bg-danger text-content">
                    Yes, cancel
                </button>
                <button type="button" wire:click="keepIt" class="chip flex-1 py-3">Keep it</button>
            </div>
        </div>
    @endif

    @forelse ($upcoming as $appointment)
        <div class="card mt-4 p-5" wire:key="upcoming-{{ $appointment['id'] }}">
            <div class="flex items-center gap-3">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-surface-raised text-sm font-semibold">
                    @if ($appointment['specialist']['photo_url'])
                        <img src="{{ $appointment['specialist']['photo_url'] }}" alt="" class="size-12 rounded-full object-cover">
                    @else
                        {{ Str::of($appointment['specialist']['name'])->explode(' ')->take(2)->map(fn (string $part): string => Str::substr($part, 0, 1))->implode('') }}
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ $appointment['service']['name'] }}</p>
                    <p class="truncate text-sm text-content-muted">with {{ $appointment['specialist']['name'] }}</p>
                    <p class="mt-1 text-sm">
                        {{ $appointment['date_label'] }} · {{ $appointment['time_label'] }}
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <a
                    href="{{ route('appointments.reschedule', $appointment['id']) }}"
                    wire:navigate
                    class="btn-accent"
                >
                    Reschedule Appointment
                </a>

                <button
                    type="button"
                    wire:click="confirmCancel({{ $appointment['id'] }})"
                    class="btn-accent bg-transparent text-danger ring-1 ring-danger/40"
                >
                    Cancel Appointment
                </button>
            </div>
        </div>
    @empty
        @if (! $error)
            <div class="card mt-6 p-8 text-center">
                <p class="text-content-muted">You have no upcoming appointments.</p>
                <a href="{{ route('book') }}" wire:navigate class="btn-accent mt-6">Book an appointment</a>
            </div>
        @endif
    @endforelse

    <h2 class="mt-10 text-2xl font-semibold tracking-tight">
        History
        @if ($past !== [])
            <span class="ml-1 text-sm font-normal text-content-muted">{{ count($past) }} past</span>
        @endif
    </h2>

    @forelse ($past as $appointment)
        <div class="card mt-4 p-5" wire:key="past-{{ $appointment['id'] }}">
            <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ $appointment['service']['name'] }}</p>
                <p class="truncate text-sm text-content-muted">
                    with {{ $appointment['specialist']['name'] }} · {{ $appointment['date_label'] }}
                </p>
            </div>

            <span @class([
                'chip shrink-0 text-xs',
                'text-danger' => $appointment['status'] === 'cancelled',
            ])>
                {{ $appointment['status_label'] }}
            </span>
            </div>

            @if ($appointment['status'] === 'completed' && ($appointment['feedback'] ?? null) === null)
                <a href="{{ route('appointments.feedback', $appointment['id']) }}" wire:navigate class="mt-4 block text-sm font-medium text-accent">
                    Rate this visit →
                </a>
            @elseif (($appointment['feedback'] ?? null) !== null)
                <p class="mt-3 text-sm text-accent" aria-label="Rated {{ $appointment['feedback']['rating'] }} out of 5">
                    {{ str_repeat('★', $appointment['feedback']['rating']) }}<span class="text-content-subtle">{{ str_repeat('★', 5 - $appointment['feedback']['rating']) }}</span>
                </p>
            @endif
        </div>
    @empty
        <div class="card mt-6 p-8 text-center">
            <p class="text-content-muted">Your past visits will appear here.</p>
        </div>
    @endforelse
</div>
