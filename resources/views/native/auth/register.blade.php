{{-- native:model is expanded at compile time, so each input needs its literal
     property name — the field list cannot be looped like the web view did. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 pt-12 gap-5" safe-area>
        <text class="text-4xl font-semibold text-theme-on-background">Create Account</text>

        @if ($error)
            <column class="w-full bg-theme-surface-variant rounded-2xl p-4">
                <text class="text-sm text-theme-destructive">{{ $error }}</text>
            </column>
        @endif

        <outlined-text-input
            label="Full Name"
            placeholder="John Doe"
            native:model="name"
            :is-error="$this->fieldError('name') !== null"
            :supporting="$this->fieldError('name')"
            class="w-full"
        />

        <outlined-text-input
            label="Email Address"
            placeholder="johndoe@email.com"
            keyboard="email"
            native:model="email"
            :is-error="$this->fieldError('email') !== null"
            :supporting="$this->fieldError('email')"
            class="w-full"
        />

        <outlined-text-input
            label="Phone Number"
            placeholder="+1 555-1234"
            keyboard="phone"
            native:model="phone"
            :is-error="$this->fieldError('phone') !== null"
            :supporting="$this->fieldError('phone')"
            class="w-full"
        />

        <outlined-text-input
            label="Password"
            placeholder="Password"
            secure
            native:model="password"
            :is-error="$this->fieldError('password') !== null"
            :supporting="$this->fieldError('password')"
            class="w-full"
        />

        <outlined-text-input
            label="Confirm Password"
            placeholder="Confirm Password"
            secure
            native:model="passwordConfirmation"
            :is-error="$this->fieldError('passwordConfirmation') !== null"
            :supporting="$this->fieldError('passwordConfirmation')"
            class="w-full"
        />

        <button label="Sign Up" variant="primary" size="lg" :loading="$loading" @press="register" class="w-full mt-3" />

        <row class="w-full justify-center items-center gap-1 pt-4">
            <text class="text-sm text-theme-on-surface-variant">Already have an account?</text>
            <text class="text-sm font-medium text-theme-primary" @press="goToSignIn">Sign in</text>
        </row>
    </column>
</scroll-view>
