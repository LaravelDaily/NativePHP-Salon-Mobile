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
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $phone = '';

    #[Validate('required|min:8|same:passwordConfirmation')]
    public string $password = '';

    public string $passwordConfirmation = '';

    public ?string $error = null;

    public function register(ApiClient $api, CustomerAuth $auth)
    {
        $this->validate();
        $this->error = null;

        try {
            $response = $api->post('auth/register', [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
            ]);
        } catch (ApiException $exception) {
            // Field-level problems (email taken, bad phone) belong on the field.
            foreach ($exception->errors as $field => $messages) {
                $this->addError($field === 'password_confirmation' ? 'passwordConfirmation' : $field, $messages[0]);
            }

            if ($exception->errors === []) {
                $this->error = $exception->getMessage();
            }

            return null;
        }

        $auth->signIn($response['token'], $response['user']['data'] ?? $response['user'] ?? null);

        return $this->redirectRoute('home', navigate: true);
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return ['passwordConfirmation' => 'password confirmation'];
    }
};
?>

<div class="screen">
    <h1 class="mt-4 text-4xl font-semibold tracking-tight">Create Account</h1>

    @if ($error)
        <p class="mt-6 rounded-2xl bg-surface-raised p-4 text-sm text-danger" data-testid="error">{{ $error }}</p>
    @endif

    <form wire:submit="register" class="mt-8 space-y-5">
        @foreach ([
            ['field' => 'name', 'label' => 'Full Name', 'type' => 'text', 'placeholder' => 'John Doe', 'autocomplete' => 'name'],
            ['field' => 'email', 'label' => 'Email Address', 'type' => 'email', 'placeholder' => 'johndoe@email.com', 'autocomplete' => 'email'],
            ['field' => 'phone', 'label' => 'Phone Number', 'type' => 'tel', 'placeholder' => '+1 555-1234', 'autocomplete' => 'tel'],
            ['field' => 'password', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Password', 'autocomplete' => 'new-password'],
            ['field' => 'passwordConfirmation', 'label' => 'Confirm Password', 'type' => 'password', 'placeholder' => 'Confirm Password', 'autocomplete' => 'new-password'],
        ] as $input)
            <div>
                <label for="{{ $input['field'] }}" class="text-sm text-content-muted">{{ $input['label'] }}</label>
                <input
                    id="{{ $input['field'] }}"
                    type="{{ $input['type'] }}"
                    wire:model="{{ $input['field'] }}"
                    autocomplete="{{ $input['autocomplete'] }}"
                    placeholder="{{ $input['placeholder'] }}"
                    class="mt-2 w-full rounded-2xl border border-hairline bg-surface px-5 py-4 text-content placeholder:text-content-subtle focus:border-accent focus:outline-none"
                >
                @error($input['field']) <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <button type="submit" class="btn-accent !mt-8" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Sign Up</span>
            <span wire:loading wire:target="register">Creating account…</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-content-muted">
        Already have an account?
        <a href="{{ route('auth.welcome') }}" wire:navigate class="font-medium text-accent">Sign in</a>
    </p>
</div>
