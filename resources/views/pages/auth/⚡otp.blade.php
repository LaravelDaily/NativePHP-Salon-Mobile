<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
class extends Component
{
    /** How long before the customer may ask for another code. */
    private const RESEND_COOLDOWN_SECONDS = 30;

    /** 'phone' while we collect the number, 'code' once one has been sent. */
    public string $step = 'phone';

    #[Validate('required|string')]
    public string $phone = '';

    public string $code = '';

    /** Optional: only used if this phone number is new to us. */
    public string $name = '';

    public ?string $error = null;

    public ?string $notice = null;

    /** Seconds left before "Resend code" becomes available again. */
    public int $resendIn = 0;

    public function sendCode(ApiClient $api): void
    {
        $this->validateOnly('phone');
        $this->error = null;

        try {
            $response = $api->post('auth/otp/request', ['phone' => $this->phone]);
        } catch (ApiException $exception) {
            $this->error = $exception->errors['phone'][0] ?? $exception->getMessage();

            return;
        }

        $this->step = 'code';
        $this->resendIn = self::RESEND_COOLDOWN_SECONDS;
        $this->reset('code');

        // In local development the API hands the code back rather than paying an
        // SMS gateway, so show it instead of making the developer read the log.
        $this->notice = isset($response['code'])
            ? "Development mode: your code is {$response['code']}"
            : 'We sent a code to your phone.';
    }

    public function verify(ApiClient $api, CustomerAuth $auth)
    {
        $this->validate(['code' => ['required', 'digits:6']]);
        $this->error = null;

        try {
            $response = $api->post('auth/otp/verify', [
                'phone' => $this->phone,
                'code' => $this->code,
                ...($this->name !== '' ? ['name' => $this->name] : []),
            ]);
        } catch (ApiException $exception) {
            $this->error = $exception->errors['code'][0] ?? $exception->getMessage();
            $this->reset('code');

            return null;
        }

        $auth->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        return $this->redirectRoute('home', navigate: true);
    }

    /** Ticked once a second by the view's countdown timer. */
    public function tickResend(): void
    {
        $this->resendIn = max(0, $this->resendIn - 1);
    }

    public function changePhone(): void
    {
        $this->reset(['step', 'code', 'error', 'notice', 'resendIn']);
    }

    public function canResend(): bool
    {
        return $this->resendIn === 0;
    }
};
?>

<div class="screen flex flex-col justify-center">
    @if ($step === 'phone')
        <h1 class="text-4xl font-semibold tracking-tight">Sign in with a code</h1>
        <p class="mt-3 text-content-muted">Enter your phone number and we'll text you a verification code.</p>

        @if ($error)
            <p class="mt-6 rounded-2xl bg-surface-raised p-4 text-sm text-danger" data-testid="error">{{ $error }}</p>
        @endif

        <form wire:submit="sendCode" class="mt-8 space-y-5">
            <div>
                <label for="phone" class="text-sm text-content-muted">Phone Number</label>
                <input
                    id="phone"
                    type="tel"
                    wire:model="phone"
                    autocomplete="tel"
                    placeholder="+1 555-1234"
                    class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
                >
                @error('phone') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-accent" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendCode">Send Code</span>
                <span wire:loading wire:target="sendCode">Sending…</span>
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-content-muted">
            <a href="{{ route('auth.welcome') }}" wire:navigate class="font-medium text-accent">Use a password instead</a>
        </p>
    @else
        <h1 class="text-4xl font-semibold tracking-tight">Enter your code</h1>
        <p class="mt-3 text-content-muted">
            Sent to {{ $phone }}.
            <button type="button" wire:click="changePhone" class="font-medium text-accent">Change</button>
        </p>

        @if ($notice)
            <p class="mt-6 rounded-2xl bg-accent-soft p-4 text-sm text-accent" data-testid="notice">{{ $notice }}</p>
        @endif

        @if ($error)
            <p class="mt-6 rounded-2xl bg-surface-raised p-4 text-sm text-danger" data-testid="error">{{ $error }}</p>
        @endif

        <form wire:submit="verify" class="mt-8 space-y-5">
            <div>
                <label for="code" class="text-sm text-content-muted">6-digit code</label>
                <input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    wire:model="code"
                    autocomplete="one-time-code"
                    placeholder="000000"
                    class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-center text-2xl tracking-[0.5em] text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
                >
                @error('code') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="text-sm text-content-muted">Your name <span class="text-content-subtle">(new customers only)</span></label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    autocomplete="name"
                    placeholder="John Doe"
                    class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
                >
            </div>

            <button type="submit" class="btn-accent" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="verify">Verify</span>
                <span wire:loading wire:target="verify">Verifying…</span>
            </button>
        </form>

        <div class="mt-8 text-center text-sm" wire:poll.1s="tickResend">
            @if ($this->canResend())
                <button type="button" wire:click="sendCode" class="font-medium text-accent">Resend code</button>
            @else
                <span class="text-content-subtle">Resend code in {{ $resendIn }}s</span>
            @endif
        </div>
    @endif
</div>
