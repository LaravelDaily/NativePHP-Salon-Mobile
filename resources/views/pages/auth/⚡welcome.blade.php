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
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public ?string $error = null;

    public function signIn(ApiClient $api, CustomerAuth $auth)
    {
        $this->validate();
        $this->error = null;

        try {
            $response = $api->post('auth/login', [
                'email' => $this->email,
                'password' => $this->password,
            ]);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->reset('password');

            return null;
        }

        $auth->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        return $this->redirectRoute('home', navigate: true);
    }
};
?>

<div class="screen flex flex-col justify-center">
    <h1 class="text-4xl font-semibold tracking-tight">Welcome Back</h1>

    @if ($error)
        <p class="mt-6 rounded-2xl bg-surface-raised p-4 text-sm text-danger" data-testid="error">{{ $error }}</p>
    @endif

    <form wire:submit="signIn" class="mt-8 space-y-5">
        <div>
            <label for="email" class="text-sm text-content-muted">Email Address</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="email"
                placeholder="Email"
                class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
            >
            @error('email') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="text-sm text-content-muted">Password</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                placeholder="Password"
                class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
            >
            @error('password') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-accent" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="signIn">Sign In</span>
            <span wire:loading wire:target="signIn">Signing in…</span>
        </button>
    </form>

    <div class="my-8 flex items-center gap-4">
        <div class="h-px flex-1 bg-hairline"></div>
        <span class="text-xs text-content-subtle">OR</span>
        <div class="h-px flex-1 bg-hairline"></div>
    </div>

    <a href="{{ route('auth.otp') }}" wire:navigate
       class="block rounded-full border border-hairline bg-surface px-6 py-4 text-center font-medium">
        Sign in with a code
    </a>

    <p class="mt-8 text-center text-sm text-content-muted">
        New here?
        <a href="{{ route('auth.register') }}" wire:navigate class="font-medium text-accent">Create an account</a>
    </p>
</div>
