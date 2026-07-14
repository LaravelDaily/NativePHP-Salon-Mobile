{{-- @change hands the toggle's new value to the bound method after its
     literal args, so setPreference('key') receives (key, value). --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full gap-1">
            <text class="text-2xl font-semibold text-theme-on-background">Preferences</text>
            <text class="text-sm text-theme-on-surface-variant">Saved now; notification delivery remains deferred until Phase 8 is enabled.</text>
        </column>

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl">
            @foreach (App\NativeComponents\Settings::PREFERENCE_LABELS as $key => $label)
                <row class="w-full items-center justify-between gap-4 p-5">
                    <text class="text-theme-on-surface">{{ $label }}</text>
                    <toggle :value="(bool) ($preferences[$key] ?? false)" @change="setPreference('{{ $key }}')" />
                </row>
                <divider class="w-full" />
            @endforeach

            <column class="p-5 w-full">
                <button label="Save preferences" variant="primary" @press="savePreferences" class="w-full" />
            </column>
        </column>

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

        <column class="w-full bg-theme-surface border border-theme-destructive/40 rounded-3xl p-5 gap-2 mt-6">
            <text class="font-semibold text-theme-destructive">Delete account</text>
            <text class="text-sm text-theme-on-surface-variant">This permanently removes your account, appointments, and sign-in tokens.</text>

            <button label="Delete account" variant="destructive" @press="deleteAccount" class="w-full mt-3" />
        </column>

        <column @press="signOut" class="w-full border border-theme-outline rounded-full px-6 py-4 items-center mt-4">
            <text class="font-medium text-theme-destructive">Sign Out</text>
        </column>
    </column>
</scroll-view>
