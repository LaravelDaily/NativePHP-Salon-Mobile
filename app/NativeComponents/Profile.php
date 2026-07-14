<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\NativeComponents\Concerns\HandlesApiErrors;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Profile extends NativeComponent
{
    use HandlesApiErrors;

    /** @var array<string, mixed>|null */
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

    public function navTitle(): string
    {
        return 'Profile';
    }

    public function mount(): void
    {
        $this->user = app(CustomerAuth::class)->user();
        $this->name = $this->user['name'] ?? '';
        $this->email = $this->user['email'] ?? '';
        $this->phone = $this->user['phone'] ?? '';

        $this->loadFavoriteStylists();
        $this->loadSalon();
    }

    public function loadFavoriteStylists(): void
    {
        try {
            /** @var array<int, array{id: int, name: string, photo_url: string|null, ...}> $favorites */
            $favorites = app(ApiClient::class)->get('me/stylists')['data'] ?? [];
            $this->favoriteStylists = $favorites;
        } catch (ApiException) {
            $this->favoriteStylists = [];
        }
    }

    public function loadSalon(): void
    {
        try {
            /** @var array{average_rating: float|int|null, feedback_count: int, ...}|null $salon */
            $salon = app(ApiClient::class)->get('salon')['data'] ?? null;
            $this->salon = $salon;
        } catch (ApiException) {
            $this->salon = null;
        }
    }

    public function toggleEditing(): void
    {
        $this->editing = ! $this->editing;
    }

    public function signOut(): void
    {
        app(CustomerAuth::class)->signOut();

        $this->replace('/welcome');
    }

    public function save(): void
    {
        if (! $this->passesValidation(
            ['name' => $this->name, 'email' => $this->email],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
            ],
        )) {
            return;
        }

        try {
            /** @var array{id: int, name: string, email: string|null, phone: string|null, ...} $user */
            $user = app(ApiClient::class)->patch('me', ['name' => $this->name, 'email' => $this->email])['data'];
            $this->user = $user;
            $this->email = $user['email'] ?? '';
            $this->editing = false;
            $this->notice = 'Profile updated.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
        }
    }

    public function requestPhoneChange(): void
    {
        if (! $this->passesValidation(
            ['phone' => $this->phone],
            ['phone' => ['required', 'string']],
        )) {
            return;
        }

        try {
            app(ApiClient::class)->post('me/phone/request', ['phone' => $this->phone]);
            $this->phoneVerificationSent = true;
            $this->notice = 'We sent a verification code to your new phone.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
        }
    }

    public function verifyPhoneChange(): void
    {
        if (! $this->passesValidation(
            ['phoneCode' => $this->phoneCode],
            ['phoneCode' => ['required', 'digits:6']],
            attributes: ['phoneCode' => 'verification code'],
        )) {
            return;
        }

        try {
            /** @var array{id: int, name: string, email: string|null, phone: string|null, ...} $user */
            $user = app(ApiClient::class)->post('me/phone/verify', ['phone' => $this->phone, 'code' => $this->phoneCode])['data'];
            $this->user = $user;
            $this->phone = $user['phone'] ?? '';
            $this->phoneCode = '';
            $this->phoneVerificationSent = false;
            $this->notice = 'Phone number verified.';
        } catch (ApiException $exception) {
            $this->addApiErrors($exception, ['code' => 'phoneCode']);
        }
    }

    public function goToStylists(): void
    {
        $this->navigate('/profile/stylists');
    }

    public function goToAppointments(): void
    {
        $this->navigate('/appointments');
    }

    public function goToSettings(): void
    {
        $this->navigate('/settings');
    }

    public function render(): View
    {
        return view('native.profile');
    }
}
