{{-- Mirrors native/stylists.blade.php: the heading pair and three favorite rows,
     each an avatar, two lines of detail and the heart button. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full gap-2">
            @include('native.skeleton.block', ['class' => 'w-[180] h-[28]'])
            @include('native.skeleton.block', ['class' => 'w-[260] h-[12]'])
        </column>

        @foreach (range(1, 3) as $stylist)
            <row class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-4 items-center gap-4">
                @include('native.skeleton.block', ['class' => 'w-[64] h-[64]'])

                <column class="flex-1 gap-2">
                    @include('native.skeleton.block', ['class' => 'w-[140] h-[14]'])
                    @include('native.skeleton.block', ['class' => 'w-[110] h-[12]'])
                    @include('native.skeleton.block', ['class' => 'w-[80] h-[12]'])
                </column>

                @include('native.skeleton.block', ['class' => 'w-[44] h-[44]'])
            </row>
        @endforeach
    </column>
</scroll-view>
