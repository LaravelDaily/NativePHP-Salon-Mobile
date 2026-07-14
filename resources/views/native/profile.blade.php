<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center gap-1">
            <column class="w-[80] h-[80] rounded-full bg-theme-primary items-center justify-center">
                <text class="text-2xl font-semibold text-theme-on-primary">{{ Str::of($user['name'] ?? '?')->substr(0, 1)->upper() }}</text>
            </column>

            <text class="text-xl font-semibold text-theme-on-surface pt-3">{{ $user['name'] ?? '' }}</text>
            <text class="text-sm text-theme-on-surface-variant">{{ $user['email'] ?? ($user['phone'] ?? '') }}</text>

            <text class="text-sm font-medium text-theme-primary pt-3" @press="toggleEditing">Edit Profile</text>
        </column>

        @if ($editing)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4">
                <outlined-text-input
                    label="Name"
                    native:model="name"
                    :is-error="$this->fieldError('name') !== null"
                    :supporting="$this->fieldError('name')"
                    class="w-full"
                />

                <outlined-text-input
                    label="Email"
                    keyboard="email"
                    native:model="email"
                    :is-error="$this->fieldError('email') !== null"
                    :supporting="$this->fieldError('email')"
                    class="w-full"
                />

                <button label="Save profile" variant="primary" @press="save" class="w-full" />
            </column>

            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4">
                <outlined-text-input
                    label="Phone number"
                    keyboard="phone"
                    native:model="phone"
                    :is-error="$this->fieldError('phone') !== null"
                    :supporting="$this->fieldError('phone')"
                    class="w-full"
                />

                @if ($phoneVerificationSent)
                    <outlined-text-input
                        label="Verification code"
                        keyboard="number"
                        :max-length="6"
                        native:model="phoneCode"
                        :is-error="$this->fieldError('phoneCode') !== null"
                        :supporting="$this->fieldError('phoneCode')"
                        class="w-full"
                    />

                    <button label="Verify new phone" variant="primary" @press="verifyPhoneChange" class="w-full" />
                @else
                    <button label="Verify new phone" variant="primary" @press="requestPhoneChange" class="w-full" />
                @endif
            </column>
        @endif

        @if ($notice)
            <column class="w-full border border-theme-outline rounded-2xl px-4 py-3">
                <text class="text-sm text-theme-on-surface-variant">{{ $notice }}</text>
            </column>
        @endif

        @if ($error)
            <column class="w-full border border-theme-destructive/40 rounded-2xl px-4 py-3">
                <text class="text-sm text-theme-destructive">{{ $error }}</text>
            </column>
        @endif

        <row @press="goToStylists" class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 items-center gap-4">
            <column class="flex-1 gap-1">
                <text class="font-medium text-theme-on-surface">My Stylists</text>
                <text class="text-sm text-theme-on-surface-variant">
                    {{ $favoriteStylists === [] ? 'Your favourite specialists' : count($favoriteStylists).' saved' }}
                </text>
            </column>

            @if ($favoriteStylists !== [])
                <row class="gap-1" a11y-label="Favorite stylist avatars">
                    @foreach (array_slice($favoriteStylists, 0, 3) as $stylist)
                        <column class="w-[36] h-[36] rounded-full bg-theme-surface-variant border-2 border-theme-surface items-center justify-center">
                            @if ($stylist['photo_url'])
                                <image src="{{ $stylist['photo_url'] }}" :width="32" :height="32" class="rounded-full" />
                            @else
                                <text class="text-xs font-semibold text-theme-on-surface">{{ Str::substr($stylist['name'], 0, 1) }}</text>
                            @endif
                        </column>
                    @endforeach
                </row>
            @endif

            <text class="text-theme-on-surface-variant/60">›</text>
        </row>

        <row @press="goToAppointments" class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 items-center">
            <column class="flex-1 gap-1">
                <text class="font-medium text-theme-on-surface">Booking History</text>
                <text class="text-sm text-theme-on-surface-variant">Past visits and ratings</text>
            </column>
            <text class="text-theme-on-surface-variant/60">›</text>
        </row>

        <row class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 items-center gap-4">
            <column class="flex-1 gap-1">
                <text class="font-medium text-theme-on-surface">Salon Feedback</text>
                <text class="text-sm text-theme-on-surface-variant">Based on {{ $salon['feedback_count'] ?? 0 }} customer reviews</text>
            </column>
            <column class="items-end gap-1">
                <text class="text-lg font-semibold text-theme-accent">★ {{ $salon['average_rating'] ?? '—' }}</text>
                <text class="text-xs text-theme-on-surface-variant/60">out of 5</text>
            </column>
        </row>

        <row @press="goToSettings" class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 items-center">
            <column class="flex-1 gap-1">
                <text class="font-medium text-theme-on-surface">App Settings</text>
                <text class="text-sm text-theme-on-surface-variant">Notifications and account</text>
            </column>
            <text class="text-theme-on-surface-variant/60">›</text>
        </row>

        <column @press="signOut" class="w-full border border-theme-outline rounded-full px-6 py-4 items-center mt-4">
            <text class="font-medium text-theme-destructive">Sign Out</text>
        </column>
    </column>
</scroll-view>
