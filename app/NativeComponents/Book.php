<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;

class Book extends NativeComponent
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

    public bool $saving = false;

    /**
     * The appointment just booked or moved. Its presence switches the screen to
     * its success state.
     *
     * @var array<string, mixed>|null
     */
    public ?array $booked = null;

    /**
     * The appointment being moved, when the screen was opened to reschedule one.
     * The service is then fixed — moving a colour appointment moves that
     * appointment, it does not become a haircut — so only the day, the
     * specialist and the time are still in play.
     *
     * @var array<string, mixed>|null
     */
    public ?array $rescheduling = null;

    public function navTitle(): string
    {
        return $this->rescheduling !== null ? 'Reschedule' : 'New Appointment';
    }

    public function mount(): void
    {
        $this->date = CarbonImmutable::today()->toDateString();

        $appointment = $this->param('appointment');

        if ($appointment !== null) {
            $this->startReschedule((int) $appointment);

            return;
        }

        $this->loadServices();
    }

    private function startReschedule(int $appointmentId): void
    {
        try {
            /** @var array<string, mixed> $existing */
            $existing = app(ApiClient::class)->get("appointments/{$appointmentId}")['data'];
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->rescheduling = $existing;
        $this->serviceId = $existing['service']['id'];
        $this->specialistId = $existing['specialist']['id'];
        $this->date = CarbonImmutable::parse($existing['starts_at'])->toDateString();

        $this->loadAvailability();
    }

    /**
     * The catalog, with a service preselected so the screen opens on something
     * to look at rather than an empty state.
     */
    public function loadServices(): void
    {
        $this->error = null;

        try {
            /** @var array<int, array<string, mixed>> $services */
            $services = app(ApiClient::class)->get('services')['data'] ?? [];
            $this->services = $services;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->serviceId ??= $this->services[0]['id'] ?? null;

        $this->loadAvailability();
    }

    public function loadAvailability(): void
    {
        if ($this->serviceId === null) {
            return;
        }

        $this->error = null;

        try {
            /** @var array<int, array<string, mixed>> $specialists */
            $specialists = app(ApiClient::class)->get('availability', [
                'service_id' => $this->serviceId,
                'date' => $this->date,
                // An appointment being moved must not block its own slot, or the
                // time it currently holds would show as taken.
                ...($this->rescheduling !== null
                    ? ['exclude_appointment_id' => $this->rescheduling['id']]
                    : []),
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

    public function selectService(int $serviceId): void
    {
        $this->serviceId = $serviceId;
        $this->startsAt = null;

        $this->loadAvailability();
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->startsAt = null;

        $this->loadAvailability();
    }

    public function selectSpecialist(int $specialistId): void
    {
        $this->specialistId = $specialistId;
        $this->startsAt = null;
    }

    public function toggleFavorite(int $specialistId): void
    {
        $index = collect($this->availability)
            ->search(fn (array $entry): bool => $entry['specialist']['id'] === $specialistId);

        if ($index === false) {
            return;
        }

        $isFavorite = (bool) ($this->availability[$index]['specialist']['is_favorite'] ?? false);

        try {
            $isFavorite
                ? app(ApiClient::class)->delete("specialists/{$specialistId}/favorite")
                : app(ApiClient::class)->post("specialists/{$specialistId}/favorite");
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->availability[$index]['specialist']['is_favorite'] = ! $isFavorite;

        usort($this->availability, fn (array $left, array $right): int => ((int) ($right['specialist']['is_favorite'] ?? false) <=> (int) ($left['specialist']['is_favorite'] ?? false))
            ?: ($left['specialist']['name'] <=> $right['specialist']['name']));
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

    public function confirm(): void
    {
        if (! $this->canConfirm()) {
            return;
        }

        $this->error = null;
        $this->saving = true;
        $this->publishFinalState();

        try {
            /** @var array<string, mixed> $appointment */
            $appointment = $this->rescheduling !== null
                ? app(ApiClient::class)->patch("appointments/{$this->rescheduling['id']}", [
                    'starts_at' => $this->startsAt,
                    'specialist_id' => $this->specialistId,
                ])['data']
                : app(ApiClient::class)->post('appointments', [
                    'service_id' => $this->serviceId,
                    'specialist_id' => $this->specialistId,
                    'starts_at' => $this->startsAt,
                ])['data'];
        } catch (ApiException $exception) {
            $this->saving = false;
            $this->handleFailure($exception);

            return;
        }

        $this->saving = false;
        $this->booked = $appointment;

        Device::vibrate();
        Dialog::toast($this->rescheduling !== null ? 'Appointment moved' : 'Booking confirmed');
    }

    /**
     * Someone may have taken the slot since the screen last loaded. That is an
     * ordinary outcome rather than a breakage: the API reports it as a validation
     * error on `starts_at`, so refresh the times and ask for another.
     */
    private function handleFailure(ApiException $exception): void
    {
        $slotTaken = isset($exception->errors['starts_at']);

        if (! $slotTaken) {
            $this->error = $exception->getMessage();

            return;
        }

        $message = $exception->errors['starts_at'][0];

        $this->startsAt = null;
        $this->loadAvailability();
        $this->error = $message;
    }

    public function bookAnother(): void
    {
        $this->booked = null;
        $this->startsAt = null;

        $this->loadAvailability();
    }

    public function viewAppointments(): void
    {
        $this->navigate('/appointments');
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
    public function days(): array
    {
        $first = CarbonImmutable::today()->addWeeks($this->weekOffset);

        return array_map(
            fn (int $day): CarbonImmutable => $first->addDays($day),
            range(0, self::STRIP_DAYS - 1),
        );
    }

    public function monthLabel(): string
    {
        return $this->days()[0]->format('F');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedService(): ?array
    {
        // When rescheduling, the catalog is never fetched: the service is
        // whatever the appointment was booked for.
        return collect($this->services)->firstWhere('id', $this->serviceId)
            ?? $this->rescheduling['service']
            ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedSpecialist(): ?array
    {
        return $this->entryForSelectedSpecialist()['specialist'] ?? null;
    }

    /**
     * The chosen specialist's slots, grouped as the API returned them.
     *
     * @return array<int, array<string, mixed>>
     */
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

    public function render(): View
    {
        return view('native.book');
    }
}
