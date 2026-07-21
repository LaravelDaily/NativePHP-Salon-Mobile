{{-- Mirrors native/home.blade.php: the salon greeting, the opening-hours card,
     the booking button and the next-appointment card. Same paddings and gaps as
     the real screen, so the data lands on top of these shapes instead of
     shoving them around. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 pt-8 gap-6" safe-area>
        <column class="w-full gap-2">
            @include('native.skeleton.block', ['class' => 'w-[90] h-[12]'])
            @include('native.skeleton.block', ['class' => 'w-[230] h-[34]'])
            @include('native.skeleton.block', ['class' => 'w-[190] h-[14]'])
        </column>

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 gap-3">
            @include('native.skeleton.block', ['class' => 'w-[120] h-[10]'])
            @include('native.skeleton.block', ['class' => 'w-full h-[14]'])
            @include('native.skeleton.block', ['class' => 'w-[210] h-[12]'])
        </column>

        @include('native.skeleton.block', ['class' => 'w-full h-[52]'])

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 gap-3">
            @include('native.skeleton.block', ['class' => 'w-[140] h-[10]'])
            @include('native.skeleton.block', ['class' => 'w-[200] h-[18]'])
            @include('native.skeleton.block', ['class' => 'w-[160] h-[12]'])
            @include('native.skeleton.block', ['class' => 'w-[180] h-[14]'])
        </column>
    </column>
</scroll-view>
