<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Illuminate\View\View;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Dialog;

class Settings extends NativeComponent
{
    private const DELETE_DIALOG = 'delete-account';

    /** The notification channels the customer can opt in or out of. */
    public const PREFERENCE_LABELS = [
        'booking_confirmed' => 'Booking confirmations',
        'appointment_reminder' => 'Appointment reminders',
        'appointment_rescheduled' => 'Reschedules',
        'appointment_cancelled' => 'Cancellations',
    ];

    /** @var array<string, bool> */
    public array $preferences = [];

    public bool $confirmingDeletion = false;

    public ?string $notice = null;

    public ?string $error = null;

    public function navTitle(): string
    {
        return 'App Settings';
    }

    public function mount(): void
    {
        $user = app(CustomerAuth::class)->user();
        $this->preferences = $user['notification_preferences'] ?? [];
    }

    public function setPreference(string $key, bool $value): void
    {
        $this->preferences[$key] = $value;
    }

    public function savePreferences(): void
    {
        try {
            /** @var array<string, bool> $preferences */
            $preferences = app(ApiClient::class)->patch('me', ['notification_preferences' => $this->preferences])['data']['notification_preferences'];
            $this->preferences = $preferences;
            $this->notice = 'Preferences saved. They will take effect when notifications are enabled in a later release.';
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function deleteAccount(): void
    {
        if (! $this->confirmingDeletion) {
            $this->confirmingDeletion = true;

            Dialog::alert(
                'Delete account?',
                'This permanently removes your appointments and signs out every device.',
                ['Keep my account', 'Delete permanently'],
            )->id(self::DELETE_DIALOG)->show();

            return;
        }

        try {
            app(ApiClient::class)->delete('me');
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        app(CustomerAuth::class)->signOut();

        $this->replace('/welcome');
    }

    #[OnNative(ButtonPressed::class)]
    public function onDialogButton(int $index, string $label, ?string $id = null): void
    {
        if ($id !== self::DELETE_DIALOG) {
            return;
        }

        if ($index === 1) {
            $this->deleteAccount();

            return;
        }

        $this->confirmingDeletion = false;
    }

    public function signOut(): void
    {
        app(CustomerAuth::class)->signOut();

        $this->replace('/welcome');
    }

    public function render(): View
    {
        return view('native.settings');
    }
}
