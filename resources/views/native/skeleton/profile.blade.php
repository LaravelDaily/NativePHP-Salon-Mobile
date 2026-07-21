{{-- Mirrors native/profile.blade.php: the avatar card, the four menu rows and
     the sign-out pill. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center gap-3">
            @include('native.skeleton.block', ['class' => 'w-[80] h-[80]'])
            @include('native.skeleton.block', ['class' => 'w-[160] h-[20]'])
            @include('native.skeleton.block', ['class' => 'w-[200] h-[12]'])
            @include('native.skeleton.block', ['class' => 'w-[90] h-[12]'])
        </column>

        @foreach (range(1, 4) as $menuRow)
            <row class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 items-center gap-4">
                <column class="flex-1 gap-2">
                    @include('native.skeleton.block', ['class' => 'w-[140] h-[14]'])
                    @include('native.skeleton.block', ['class' => 'w-[190] h-[12]'])
                </column>

                @include('native.skeleton.block', ['class' => 'w-[36] h-[36]'])
            </row>
        @endforeach

        @include('native.skeleton.block', ['class' => 'w-full h-[52] mt-4'])
    </column>
</scroll-view>
