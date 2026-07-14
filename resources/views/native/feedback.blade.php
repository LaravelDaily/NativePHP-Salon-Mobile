<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        @if ($submitted)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center gap-2 mt-6">
                <text class="text-4xl text-theme-accent">★★★★★</text>
                <text class="text-2xl font-semibold text-theme-on-surface pt-2">Thank you</text>
                <text class="text-theme-on-surface-variant text-center">Your feedback helps StyleHub keep every visit sharp.</text>

                <button label="Back to history" variant="primary" size="lg" @press="backToHistory" class="w-full mt-4" />
            </column>
        @elseif ($visit)
            <column class="w-full gap-1">
                <text class="text-2xl font-semibold text-theme-on-background">How was your visit?</text>
                <text class="text-theme-on-surface-variant">{{ $visit['service']['name'] }} with {{ $visit['specialist']['name'] }}</text>
            </column>

            <column class="w-full gap-3 pt-4">
                <text class="text-sm font-medium text-theme-on-background">Your rating</text>

                <row class="gap-2" a11y-label="Rating">
                    @foreach (range(1, 5) as $star)
                        <column
                            @press="rate({{ $star }})"
                            class="w-[48] h-[48] rounded-full bg-theme-surface-variant items-center justify-center"
                            a11y-label="{{ $star }} star rating"
                        >
                            <text class="text-2xl {{ $rating >= $star ? 'text-theme-accent' : 'text-theme-on-surface-variant/50' }}">★</text>
                        </column>
                    @endforeach
                </row>

                @if ($this->fieldError('rating'))
                    <text class="text-sm text-theme-destructive">{{ $this->fieldError('rating') }}</text>
                @endif
            </column>

            <column class="w-full gap-2 pt-2">
                <outlined-text-input
                    label="Anything else? Optional"
                    placeholder="Tell us what stood out..."
                    native:model="comment"
                    multiline
                    :min-lines="5"
                    :max-lines="10"
                    :max-length="1000"
                    :is-error="$this->fieldError('comment') !== null"
                    :supporting="$this->fieldError('comment')"
                    class="w-full"
                />

                @if ($this->fieldError('appointment'))
                    <text class="text-sm text-theme-destructive">{{ $this->fieldError('appointment') }}</text>
                @endif
            </column>

            @if ($error)
                <column class="w-full border border-theme-destructive/40 rounded-2xl px-4 py-3">
                    <text class="text-sm text-theme-destructive">{{ $error }}</text>
                </column>
            @endif

            <button
                :label="$saving ? 'Submitting…' : 'Submit feedback'"
                variant="primary"
                size="lg"
                :loading="$saving"
                @press="submit"
                class="w-full mt-2"
            />
        @elseif ($error)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 items-center mt-6">
                <text class="text-theme-on-surface-variant text-center">{{ $error }}</text>
            </column>
        @endif
    </column>
</scroll-view>
