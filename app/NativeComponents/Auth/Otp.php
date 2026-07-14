<?php

namespace App\NativeComponents\Auth;

use App\Exceptions\ApiException;
use App\NativeComponents\Concerns\HandlesApiErrors;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class Otp extends NativeComponent
{
    use HandlesApiErrors;

    /** How long before the customer may ask for another code. */
    private const RESEND_COOLDOWN_SECONDS = 30;

    /** 'phone' while we collect the number, 'code' once one has been sent. */
    public string $step = 'phone';

    public string $phone = '';

    public string $code = '';

    /** Optional: only used if this phone number is new to us. */
    public string $name = '';

    public ?string $notice = null;

    /** Seconds left before "Resend code" becomes available again. */
    public int $resendIn = 0;

    public bool $loading = false;

    public function sendCode(): void
    {
        if (! $this->passesValidation(
            ['phone' => $this->phone],
            ['phone' => ['required', 'string']],
        )) {
            return;
        }

        $this->error = null;
        $this->loading = true;
        $this->publishFinalState();

        try {
            $response = app(ApiClient::class)->post('auth/otp/request', ['phone' => $this->phone]);
        } catch (ApiException $exception) {
            $this->error = $exception->errors['phone'][0] ?? $exception->getMessage();
            $this->loading = false;

            return;
        }

        $this->loading = false;
        $this->step = 'code';
        $this->resendIn = self::RESEND_COOLDOWN_SECONDS;
        $this->code = '';

        // In local development the API hands the code back rather than paying an
        // SMS gateway, so show it instead of making the developer read the log.
        $this->notice = isset($response['code'])
            ? "Development mode: your code is {$response['code']}"
            : 'We sent a code to your phone.';
    }

    public function verify(): void
    {
        if (! $this->passesValidation(
            ['code' => $this->code],
            ['code' => ['required', 'digits:6']],
        )) {
            return;
        }

        $this->error = null;
        $this->loading = true;
        $this->publishFinalState();

        try {
            $response = app(ApiClient::class)->post('auth/otp/verify', [
                'phone' => $this->phone,
                'code' => $this->code,
                ...($this->name !== '' ? ['name' => $this->name] : []),
            ]);
        } catch (ApiException $exception) {
            $this->error = $exception->errors['code'][0] ?? $exception->getMessage();
            $this->code = '';
            $this->loading = false;

            return;
        }

        app(CustomerAuth::class)->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        $this->replace('/');
    }

    /** Ticked once a second by the render loop while the screen is open. */
    #[Poll(1000)]
    public function tickResend(): void
    {
        $this->resendIn = max(0, $this->resendIn - 1);
    }

    public function changePhone(): void
    {
        $this->step = 'phone';
        $this->code = '';
        $this->error = null;
        $this->notice = null;
        $this->resendIn = 0;
    }

    public function canResend(): bool
    {
        return $this->resendIn === 0;
    }

    public function goToPassword(): void
    {
        $this->navigate('/welcome');
    }

    public function render(): View
    {
        return view('native.auth.otp');
    }
}
