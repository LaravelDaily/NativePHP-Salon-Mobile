<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;

new class extends Component
{
    /** Days shown in the date strip at once, as in the mockup. */
    private const STRIP_DAYS = 7;

    /** @var array<int, array<string, mixed>> */
    public array $services = [];

    /**
     * Availability for the chosen service and date: one entry per specialist who
     * performs it, each with their slots already grouped into Morning/Afternoon/
     * Evening by the API. Switching specialist is therefore free — the slots for
     * every one of them are already here.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $availability = [];

    public ?int $serviceId = null;

    public ?int $specialistId = null;

    public string $date = '';

    /** The chosen slot, held as the ISO start time the API gave us. */
    public ?string $startsAt = null;

    /** How many weeks forward the date strip has been paged. */
    public int $weekOffset = 0;

    public ?string $error = null;

    /**
     * The appointment just booked. Its presence switches the screen to its
     * success state.
     *
     * @var array<string, mixed>|null
     */
    public ?array $booked = null;

    public function mount(ApiClient $api): void
    {
        $this->date = CarbonImmutable::today()->toDateString();

        $this->loadServices($api);
    }

    /**
     * The catalog, with a service preselected so the screen opens on something
     * to look at rather than an empty state.
     */
    public function loadServices(ApiClient $api): void
    {
        $this->error = null;

        try {
            /** @var array<int, array<string, mixed>> $services */
            $services = $api->get('services')['data'] ?? [];
            $this->services = $services;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->serviceId ??= $this->services[0]['id'] ?? null;

        $this->loadAvailability($api);
    }

    public function loadAvailability(ApiClient $api): void
    {
        if ($this->serviceId === null) {
            return;
        }

        $this->error = null;

        try {
            /** @var array<int, array<string, mixed>> $specialists */
            $specialists = $api->get('availability', [
                'service_id' => $this->serviceId,
                'date' => $this->date,
            ])['data']['specialists'] ?? [];

            $this->availability = $specialists;
        } catch (ApiException $exception) {
            $this->availability = [];
            $this->error = $exception->getMessage();

            return;
        }

        $this->settleSelection();
    }

    /**
     * Keep the chosen specialist and time honest after the slots underneath them
     * change: drop a specialist who cannot work this day, and a time no longer
     * offered. Falling back to the first specialist with slots means the screen
     * shows times rather than an empty list.
     */
    private function settleSelection(): void
    {
        $free = array_values(array_filter(
            $this->availability,
            fn (array $entry): bool => $entry['slot_count'] > 0,
        ));

        $chosenIsFree = collect($free)
            ->contains(fn (array $entry): bool => $entry['specialist']['id'] === $this->specialistId);

        if (! $chosenIsFree) {
            $this->specialistId = $free[0]['specialist']['id'] ?? null;
        }

        if ($this->startsAt !== null && ! $this->isOffered($this->startsAt)) {
            $this->startsAt = null;
        }
    }

    private function isOffered(string $startsAt): bool
    {
        return collect($this->slotsByPeriod())
            ->flatMap(fn (array $period): array => $period['slots'])
            ->contains(fn (array $slot): bool => $slot['starts_at'] === $startsAt);
    }

    public function selectService(int $serviceId, ApiClient $api): void
    {
        $this->serviceId = $serviceId;
        $this->startsAt = null;

        $this->loadAvailability($api);
    }

    public function selectDate(string $date, ApiClient $api): void
    {
        $this->date = $date;
        $this->startsAt = null;

        $this->loadAvailability($api);
    }

    public function selectSpecialist(int $specialistId): void
    {
        $this->specialistId = $specialistId;
        $this->startsAt = null;
    }

    public function selectTime(string $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function previousWeek(): void
    {
        $this->weekOffset = max(0, $this->weekOffset - 1);
    }

    public function nextWeek(): void
    {
        $this->weekOffset++;
    }

    public function confirm(ApiClient $api): void
    {
        if (! $this->canConfirm()) {
            return;
        }

        $this->error = null;

        try {
            /** @var array<string, mixed> $appointment */
            $appointment = $api->post('appointments', [
                'service_id' => $this->serviceId,
                'specialist_id' => $this->specialistId,
                'starts_at' => $this->startsAt,
            ])['data'];
        } catch (ApiException $exception) {
            $this->handleFailure($exception, $api);

            return;
        }

        $this->booked = $appointment;

        Device::vibrate();
        Dialog::toast('Booking confirmed');
    }

    /**
     * Someone may have taken the slot since the screen last loaded. That is an
     * ordinary outcome rather than a breakage: the API reports it as a validation
     * error on `starts_at`, so refresh the times and ask for another.
     */
    private function handleFailure(ApiException $exception, ApiClient $api): void
    {
        $slotTaken = isset($exception->errors['starts_at']);

        if (! $slotTaken) {
            $this->error = $exception->getMessage();

            return;
        }

        $message = $exception->errors['starts_at'][0];

        $this->startsAt = null;
        $this->loadAvailability($api);
        $this->error = $message;
    }

    public function bookAnother(ApiClient $api): void
    {
        $this->booked = null;
        $this->startsAt = null;

        $this->loadAvailability($api);
    }

    public function canConfirm(): bool
    {
        return $this->serviceId !== null
            && $this->specialistId !== null
            && $this->startsAt !== null;
    }

    /**
     * The week of dates on offer, starting today.
     *
     * @return array<int, CarbonImmutable>
     */
    #[Computed]
    public function days(): array
    {
        $first = CarbonImmutable::today()->addWeeks($this->weekOffset);

        return array_map(
            fn (int $day): CarbonImmutable => $first->addDays($day),
            range(0, self::STRIP_DAYS - 1),
        );
    }

    #[Computed]
    public function monthLabel(): string
    {
        return $this->days()[0]->format('F');
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function selectedService(): ?array
    {
        return collect($this->services)->firstWhere('id', $this->serviceId);
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function selectedSpecialist(): ?array
    {
        return $this->entryForSelectedSpecialist()['specialist'] ?? null;
    }

    /**
     * The chosen specialist's slots, grouped as the API returned them.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function slotsByPeriod(): array
    {
        /** @var array<int, array<string, mixed>> $periods */
        $periods = $this->entryForSelectedSpecialist()['periods'] ?? [];

        return $periods;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entryForSelectedSpecialist(): ?array
    {
        return collect($this->availability)
            ->first(fn (array $entry): bool => $entry['specialist']['id'] === $this->specialistId);
    }

    public function timeLabel(): ?string
    {
        if ($this->startsAt === null) {
            return null;
        }

        return CarbonImmutable::parse($this->startsAt)->format('g:i A');
    }
};
?>

@php
    $serviceIcons = ['scissors' => '✂️', 'razor' => '🪒', 'sparkles' => '✨', 'palette' => '🎨'];
@endphp

<div class="screen">
    <native:top-bar title="New Appointment" :show-navigation-icon="false" />

    @if ($booked)
        <div class="card mt-6 p-6 text-center" data-testid="booking-confirmed">
            <p class="text-4xl text-accent">&check;</p>

            <h2 class="mt-3 text-2xl font-semibold tracking-tight">Booking confirmed</h2>

            <p class="mt-2 text-content-muted">
                {{ $booked['service']['name'] }} with {{ $booked['specialist']['name'] }}
            </p>
            <p class="mt-1">{{ $booked['date_label'] }} at {{ $booked['time_label'] }}</p>

            <a href="{{ route('appointments') }}" wire:navigate class="btn-accent mt-6">My appointments</a>

            <button type="button" wire:click="bookAnother" class="mt-4 text-sm text-content-muted">
                Book another
            </button>
        </div>
    @else
        <h2 class="text-2xl font-semibold tracking-tight">New Appointment</h2>

        @if ($error)
            <p class="mt-4 rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger" data-testid="booking-error">
                {{ $error }}
            </p>
        @endif

        <h3 class="mt-6 text-lg font-semibold">Choose Service</h3>

        <div class="mt-3 space-y-2">
            @foreach ($services as $service)
                <button
                    type="button"
                    wire:key="service-{{ $service['id'] }}"
                    wire:click="selectService({{ $service['id'] }})"
                    @class([
                        'card flex w-full items-center gap-3 px-4 py-3 text-left',
                        'border-accent' => $service['id'] === $serviceId,
                    ])
                >
                    <span class="text-lg">{{ $serviceIcons[$service['icon']] ?? '💈' }}</span>

                    <span class="flex-1">
                        <span class="block font-medium">{{ $service['name'] }}</span>
                        <span class="block text-xs text-content-muted">
                            {{ $service['duration_minutes'] }} min · {{ $service['price_formatted'] }}
                        </span>
                    </span>

                    @if ($service['id'] === $serviceId)
                        <span class="text-accent">&check;</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ $this->monthLabel }}</h3>

            <div class="flex gap-2">
                <button type="button" wire:click="previousWeek" @disabled($weekOffset === 0) class="chip disabled:opacity-40">&lsaquo;</button>
                <button type="button" wire:click="nextWeek" class="chip">&rsaquo;</button>
            </div>
        </div>

        <div class="mt-3 flex gap-2 overflow-x-auto">
            @foreach ($this->days as $day)
                <button
                    type="button"
                    wire:key="day-{{ $day->toDateString() }}"
                    wire:click="selectDate('{{ $day->toDateString() }}')"
                    @class([
                        'chip shrink-0 px-4 py-2 text-center',
                        'chip-active' => $day->toDateString() === $date,
                    ])
                >
                    <span class="block text-xs">{{ $day->format('D') }}</span>
                    <span class="block text-base font-semibold">{{ $day->format('d') }}</span>
                </button>
            @endforeach
        </div>

        <h3 class="mt-8 text-lg font-semibold">Choose Specialist</h3>

        @if ($availability === [])
            <p class="mt-3 text-sm text-content-muted">No specialists offer this service yet.</p>
        @else
            <div class="mt-3 flex gap-4 overflow-x-auto pb-1">
                @foreach ($availability as $entry)
                    @php($specialist = $entry['specialist'])

                    <button
                        type="button"
                        wire:key="specialist-{{ $specialist['id'] }}"
                        wire:click="selectSpecialist({{ $specialist['id'] }})"
                        @disabled($entry['slot_count'] === 0)
                        @class(['w-20 shrink-0 text-center', 'opacity-40' => $entry['slot_count'] === 0])
                    >
                        <span @class([
                            'relative mx-auto flex size-16 items-center justify-center rounded-full bg-surface-raised font-semibold',
                            'ring-2 ring-accent' => $specialist['id'] === $specialistId,
                        ])>
                            @if ($specialist['photo_url'])
                                <img src="{{ $specialist['photo_url'] }}" alt="" class="size-16 rounded-full object-cover">
                            @else
                                {{ Str::of($specialist['name'])->explode(' ')->take(2)->map(fn (string $part): string => Str::substr($part, 0, 1))->implode('') }}
                            @endif

                            @if ($specialist['id'] === $specialistId)
                                <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-accent text-xs text-accent-ink">&check;</span>
                            @endif
                        </span>

                        <span class="mt-2 block truncate text-xs text-content-muted">{{ $specialist['name'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <div wire:loading.class="opacity-50" wire:target="selectService,selectDate,selectSpecialist">
            @forelse ($this->slotsByPeriod as $period)
                <div wire:key="period-{{ $period['period'] }}">
                    <h3 class="mt-8 text-lg font-semibold">{{ $period['label'] }}</h3>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($period['slots'] as $slot)
                            <button
                                type="button"
                                wire:key="slot-{{ $slot['starts_at'] }}"
                                wire:click="selectTime('{{ $slot['starts_at'] }}')"
                                @class(['chip', 'chip-active' => $slot['starts_at'] === $startsAt])
                            >
                                {{ $slot['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="mt-8 text-sm text-content-muted" data-testid="no-slots">
                    No times available on this day. Try another date or specialist.
                </p>
            @endforelse
        </div>

        <div class="card mt-8 p-5">
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-widest text-content-subtle">Service</dt>
                    <dd class="mt-1">{{ $this->selectedService['name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-widest text-content-subtle">Date</dt>
                    <dd class="mt-1">{{ \Carbon\CarbonImmutable::parse($date)->format('D, j M') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-widest text-content-subtle">Time</dt>
                    <dd class="mt-1">{{ $this->timeLabel() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-widest text-content-subtle">Specialist</dt>
                    <dd class="mt-1">{{ $this->selectedSpecialist['name'] ?? '—' }}</dd>
                </div>
            </dl>

            <button
                type="button"
                wire:click="confirm"
                @disabled(! $this->canConfirm())
                class="btn-accent mt-5 disabled:bg-surface-raised disabled:text-content-subtle"
            >
                <span wire:loading.remove wire:target="confirm">Confirm Booking</span>
                <span wire:loading wire:target="confirm">Booking…</span>
            </button>
        </div>
    @endif
</div>
