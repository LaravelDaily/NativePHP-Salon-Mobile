<scroll-view class="bg-theme-background">
    <column class="w-full p-5 pt-16 gap-5" safe-area>
        @if ($step === 'phone')
            <text class="text-4xl font-semibold text-theme-on-background">Sign in with a code</text>
            <text class="text-theme-on-surface-variant">Enter your phone number and we'll text you a verification code.</text>

            @if ($error)
                <column class="w-full bg-theme-surface-variant rounded-2xl p-4">
                    <text class="text-sm text-theme-destructive">{{ $error }}</text>
                </column>
            @endif

            <outlined-text-input
                label="Phone Number"
                placeholder="+1 555-1234"
                keyboard="phone"
                native:model="phone"
                :is-error="$this->fieldError('phone') !== null"
                :supporting="$this->fieldError('phone')"
                class="w-full"
            />

            <button label="Send Code" variant="primary" size="lg" :loading="$loading" @press="sendCode" class="w-full" />

            <row class="w-full justify-center pt-4">
                <text class="text-sm font-medium text-theme-primary" @press="goToPassword">Use a password instead</text>
            </row>
        @else
            <text class="text-4xl font-semibold text-theme-on-background">Enter your code</text>

            <row class="w-full items-center gap-1">
                <text class="text-theme-on-surface-variant">Sent to {{ $phone }}.</text>
                <text class="font-medium text-theme-primary" @press="changePhone">Change</text>
            </row>

            @if ($notice)
                <column class="w-full bg-theme-surface-variant rounded-2xl p-4">
                    <text class="text-sm text-theme-primary">{{ $notice }}</text>
                </column>
            @endif

            @if ($error)
                <column class="w-full bg-theme-surface-variant rounded-2xl p-4">
                    <text class="text-sm text-theme-destructive">{{ $error }}</text>
                </column>
            @endif

            <outlined-text-input
                label="6-digit code"
                placeholder="000000"
                keyboard="number"
                :max-length="6"
                native:model="code"
                :is-error="$this->fieldError('code') !== null"
                :supporting="$this->fieldError('code')"
                class="w-full"
            />

            <outlined-text-input
                label="Your name (new customers only)"
                placeholder="John Doe"
                native:model="name"
                class="w-full"
            />

            <button label="Verify" variant="primary" size="lg" :loading="$loading" @press="verify" class="w-full" />

            <row class="w-full justify-center pt-4">
                @if ($this->canResend())
                    <text class="text-sm font-medium text-theme-primary" @press="sendCode">Resend code</text>
                @else
                    <text class="text-sm text-theme-on-surface-variant">Resend code in {{ $resendIn }}s</text>
                @endif
            </row>
        @endif
    </column>
</scroll-view>
