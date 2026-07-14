<?php

namespace App\NativeComponents\Auth;

use App\Exceptions\ApiException;
use App\NativeComponents\Concerns\HandlesApiErrors;
use App\Services\ApiClient;
use App\Services\CustomerAuth;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Register extends NativeComponent
{
    use HandlesApiErrors;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $loading = false;

    public function register(): void
    {
        if (! $this->passesValidation(
            [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'passwordConfirmation' => $this->passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email'],
                'phone' => ['required', 'string'],
                'password' => ['required', 'min:8', 'same:passwordConfirmation'],
            ],
            attributes: ['passwordConfirmation' => 'password confirmation'],
        )) {
            return;
        }

        $this->error = null;
        $this->loading = true;
        $this->publishFinalState();

        try {
            $response = app(ApiClient::class)->post('auth/register', [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
            ]);
        } catch (ApiException $exception) {
            // Field-level problems (email taken, bad phone) belong on the field.
            $this->addApiErrors($exception, ['password_confirmation' => 'passwordConfirmation']);
            $this->loading = false;

            return;
        }

        app(CustomerAuth::class)->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        $this->replace('/');
    }

    public function goToSignIn(): void
    {
        $this->navigate('/welcome');
    }

    public function render(): View
    {
        return view('native.auth.register');
    }
}
