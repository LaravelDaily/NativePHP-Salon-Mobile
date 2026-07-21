{{-- Mirrors native/book.blade.php in its picking state: the service list, the
     week strip, the specialist row and the summary card. The day strip is the
     one part the real screen can draw without the API, but keeping it grey here
     avoids a half-live frame where dates are tappable and nothing else is. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        @include('native.skeleton.block', ['class' => 'w-[220] h-[28]'])

        @include('native.skeleton.block', ['class' => 'w-[150] h-[20]'])

        <column class="w-full gap-2">
            @foreach (range(1, 3) as $service)
                <row class="w-full items-center gap-3 px-4 py-3 bg-theme-surface rounded-3xl border border-theme-outline">
                    @include('native.skeleton.block', ['class' => 'w-[24] h-[24]'])

                    <column class="flex-1 gap-2">
                        @include('native.skeleton.block', ['class' => 'w-[160] h-[14]'])
                        @include('native.skeleton.block', ['class' => 'w-[110] h-[10]'])
                    </column>
                </row>
            @endforeach
        </column>

        <row class="w-full items-center justify-between pt-4">
            @include('native.skeleton.block', ['class' => 'w-[110] h-[20]'])

            <row class="gap-2">
                @include('native.skeleton.block', ['class' => 'w-[44] h-[32]'])
                @include('native.skeleton.block', ['class' => 'w-[44] h-[32]'])
            </row>
        </row>

        <row class="gap-2">
            @foreach (range(1, 6) as $day)
                @include('native.skeleton.block', ['class' => 'w-[48] h-[52]'])
            @endforeach
        </row>

        @include('native.skeleton.block', ['class' => 'w-[170] h-[20] mt-4'])

        <row class="gap-4">
            @foreach (range(1, 4) as $specialist)
                <column class="items-center w-[80] gap-2">
                    @include('native.skeleton.block', ['class' => 'w-[64] h-[64]'])
                    @include('native.skeleton.block', ['class' => 'w-[56] h-[10]'])
                </column>
            @endforeach
        </row>

        @include('native.skeleton.block', ['class' => 'w-[130] h-[20] mt-4'])

        <column class="w-full gap-2">
            @foreach (range(1, 2) as $slotRow)
                <row class="w-full gap-2">
                    @foreach (range(1, 3) as $slot)
                        <column class="flex-1">
                            @include('native.skeleton.block', ['class' => 'w-full h-[36]'])
                        </column>
                    @endforeach
                </row>
            @endforeach
        </column>

        <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4 mt-4">
            @foreach (range(1, 2) as $summaryRow)
                <row class="w-full">
                    <column class="flex-1 gap-2">
                        @include('native.skeleton.block', ['class' => 'w-[70] h-[10]'])
                        @include('native.skeleton.block', ['class' => 'w-[110] h-[12]'])
                    </column>
                    <column class="flex-1 gap-2">
                        @include('native.skeleton.block', ['class' => 'w-[70] h-[10]'])
                        @include('native.skeleton.block', ['class' => 'w-[110] h-[12]'])
                    </column>
                </row>
            @endforeach

            @include('native.skeleton.block', ['class' => 'w-full h-[52] mt-1'])
        </column>
    </column>
</scroll-view>
