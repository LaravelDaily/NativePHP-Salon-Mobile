<?php

namespace App\NativeComponents\Auth;

use App\Exceptions\ApiException;
use App\NativeComponents\Concerns\HandlesApiErrors;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Welcome extends NativeComponent
{
    use HandlesApiErrors;

    public string $email = '';

    public string $password = '';

    public bool $loading = false;

    public function signIn(): void
    {
        if (! $this->passesValidation(
            ['email' => $this->email, 'password' => $this->password],
            ['email' => ['required', 'email'], 'password' => ['required']],
        )) {
            return;
        }

        $this->error = null;

        // The HTTP call blocks the handler, so paint the loading state first.
        $this->loading = true;
        $this->publishFinalState();

        try {
            $response = app(ApiClient::class)->post('auth/login', [
                'email' => $this->email,
                'password' => $this->password,
            ]);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->password = '';
            $this->loading = false;

            return;
        }

        app(CustomerAuth::class)->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        // replace() rather than navigate(): Back from home must not return here.
        $this->replace('/');
    }

    public function goToOtp(): void
    {
        $this->navigate('/sign-in-with-code');
    }

    public function goToRegister(): void
    {
        $this->navigate('/register');
    }

    public function render(): View
    {
        return view('native.auth.welcome');
    }
}
