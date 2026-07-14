<scroll-view class="bg-theme-background">
    <column class="w-full p-5 pt-16 gap-5" safe-area>
        <text class="text-4xl font-semibold text-theme-on-background">Welcome Back</text>

        @if ($error)
            <column class="w-full bg-theme-surface-variant rounded-2xl p-4">
                <text class="text-sm text-theme-destructive">{{ $error }}</text>
            </column>
        @endif

        <outlined-text-input
            label="Email Address"
            placeholder="Email"
            keyboard="email"
            native:model="email"
            :is-error="$this->fieldError('email') !== null"
            :supporting="$this->fieldError('email')"
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

        <button label="Sign In" variant="primary" size="lg" :loading="$loading" @press="signIn" class="w-full" />

        <row class="w-full items-center gap-4 py-3">
            <divider class="flex-1" />
            <text class="text-xs text-theme-on-surface-variant">OR</text>
            <divider class="flex-1" />
        </row>

        <button label="Sign in with a code" variant="secondary" size="lg" @press="goToOtp" class="w-full" />

        <row class="w-full justify-center items-center gap-1 pt-4">
            <text class="text-sm text-theme-on-surface-variant">New here?</text>
            <text class="text-sm font-medium text-theme-primary" @press="goToRegister">Create an account</text>
        </row>
    </column>
</scroll-view>
