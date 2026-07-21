{{-- Mirrors native/feedback.blade.php in its unsubmitted state: the visit
     heading, the five rating circles, the comment box and the submit button. --}}
<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full gap-2">
            @include('native.skeleton.block', ['class' => 'w-[240] h-[28]'])
            @include('native.skeleton.block', ['class' => 'w-[200] h-[14]'])
        </column>

        <column class="w-full gap-3 pt-4">
            @include('native.skeleton.block', ['class' => 'w-[90] h-[12]'])

            <row class="gap-2">
                @foreach (range(1, 5) as $star)
                    @include('native.skeleton.block', ['class' => 'w-[48] h-[48]'])
                @endforeach
            </row>
        </column>

        <column class="w-full pt-2">
            @include('native.skeleton.block', ['class' => 'w-full h-[140] rounded-3xl'])
        </column>

        @include('native.skeleton.block', ['class' => 'w-full h-[52] mt-2'])
    </column>
</scroll-view>
