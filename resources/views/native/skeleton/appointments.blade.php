{{-- Mirrors native/appointments.blade.php: two upcoming cards with an avatar and
     two action buttons, then the History heading and a couple of past rows. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        @include('native.skeleton.block', ['class' => 'w-[250] h-[28]'])

        @foreach (range(1, 2) as $card)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4">
                <row class="w-full items-center gap-3">
                    @include('native.skeleton.block', ['class' => 'w-[48] h-[48]'])

                    <column class="flex-1 gap-2">
                        @include('native.skeleton.block', ['class' => 'w-[150] h-[14]'])
                        @include('native.skeleton.block', ['class' => 'w-[120] h-[12]'])
                        @include('native.skeleton.block', ['class' => 'w-[180] h-[12]'])
                    </column>
                </row>

                @include('native.skeleton.block', ['class' => 'w-full h-[44]'])
                @include('native.skeleton.block', ['class' => 'w-full h-[44]'])
            </column>
        @endforeach

        <row class="w-full items-end gap-2 pt-6">
            @include('native.skeleton.block', ['class' => 'w-[110] h-[28]'])
        </row>

        @foreach (range(1, 2) as $card)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-2">
                <row class="w-full items-center gap-3">
                    <column class="flex-1 gap-2">
                        @include('native.skeleton.block', ['class' => 'w-[140] h-[14]'])
                        @include('native.skeleton.block', ['class' => 'w-[200] h-[12]'])
                    </column>

                    @include('native.skeleton.block', ['class' => 'w-[80] h-[32]'])
                </row>
            </column>
        @endforeach
    </column>
</scroll-view>
