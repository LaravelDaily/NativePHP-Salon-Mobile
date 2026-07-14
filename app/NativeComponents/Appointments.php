<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Illuminate\View\View;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\Dialog;

class Appointments extends NativeComponent
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

    public function navTitle(): string
    {
        return 'Appointments';
    }

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->error = null;

        $api = app(ApiClient::class);

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
     * Cancelling is destructive and cannot be undone, so it is always
     * confirmed through a native dialog before anything is sent.
     */
    public function confirmCancel(int $appointmentId): void
    {
        $this->cancelling = $appointmentId;
        $this->notice = null;

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

        $index === 1 ? $this->cancel() : $this->keepIt();
    }

    public function keepIt(): void
    {
        $this->cancelling = null;
    }

    public function cancel(): void
    {
        if ($this->cancelling === null) {
            return;
        }

        $this->error = null;

        try {
            app(ApiClient::class)->delete("appointments/{$this->cancelling}");
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

        $this->load();
    }

    public function reschedule(int $appointmentId): void
    {
        $this->navigate("/appointments/{$appointmentId}/reschedule");
    }

    public function rateVisit(int $appointmentId): void
    {
        $this->navigate("/appointments/{$appointmentId}/feedback");
    }

    public function goToBook(): void
    {
        $this->navigate('/book');
    }

    public function render(): View
    {
        return view('native.appointments');
    }
}
