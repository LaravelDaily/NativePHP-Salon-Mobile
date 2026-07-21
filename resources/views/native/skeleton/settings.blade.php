{{-- Mirrors native/settings.blade.php: the heading pair, the preferences card
     with one row per notification channel, and the delete-account card. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full gap-2">
            @include('native.skeleton.block', ['class' => 'w-[170] h-[28]'])
            @include('native.skeleton.block', ['class' => 'w-[270] h-[12]'])
        </column>

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl">
            @foreach (App\NativeComponents\Settings::PREFERENCE_LABELS as $label)
                <row class="w-full items-center justify-between gap-4 p-5">
                    @include('native.skeleton.block', ['class' => 'w-[180] h-[14]'])
                    @include('native.skeleton.block', ['class' => 'w-[48] h-[28]'])
                </row>
                <divider class="w-full" />
            @endforeach

            <column class="p-5 w-full">
                @include('native.skeleton.block', ['class' => 'w-full h-[44]'])
            </column>
        </column>

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-3 mt-6">
            @include('native.skeleton.block', ['class' => 'w-[150] h-[16]'])
            @include('native.skeleton.block', ['class' => 'w-full h-[12]'])
            @include('native.skeleton.block', ['class' => 'w-full h-[44] mt-3'])
        </column>

        @include('native.skeleton.block', ['class' => 'w-full h-[52] mt-4'])
    </column>
</scroll-view>
