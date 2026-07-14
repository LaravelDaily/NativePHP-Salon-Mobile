@php
    $initials = fn (string $name): string => Str::of($name)
        ->explode(' ')
        ->take(2)
        ->map(fn (string $part): string => Str::substr($part, 0, 1))
        ->implode('');
@endphp

<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <column class="w-full gap-1">
            <text class="text-2xl font-semibold text-theme-on-background">Your favorites</text>
            <text class="text-sm text-theme-on-surface-variant">Keep the people who know your style close at hand.</text>
        </column>

        @if ($error)
            <column class="w-full border border-theme-destructive/40 rounded-2xl px-4 py-3">
                <text class="text-sm text-theme-destructive">{{ $error }}</text>
            </column>
        @endif

        @if ($notice)
            <column class="w-full border border-theme-outline rounded-2xl px-4 py-3">
                <text class="text-sm text-theme-on-surface-variant">{{ $notice }}</text>
            </column>
        @endif

        @forelse ($stylists as $stylist)
            <row class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-4 items-center gap-4">
                <column class="w-[64] h-[64] rounded-full bg-theme-surface-variant items-center justify-center">
                    @if ($stylist['photo_url'])
                        <image src="{{ $stylist['photo_url'] }}" :width="60" :height="60" class="rounded-full" />
                    @else
                        <text class="font-semibold text-theme-on-surface">{{ $initials($stylist['name']) }}</text>
                    @endif
                </column>

                <column class="flex-1 gap-1">
                    <text class="font-semibold text-theme-on-surface">{{ $stylist['name'] }}</text>
                    <text class="text-sm text-theme-on-surface-variant">{{ $stylist['title'] ?? 'StyleHub specialist' }}</text>

                    @if ($stylist['average_rating'] !== null)
                        <row class="items-center gap-1 pt-1">
                            <text class="text-sm text-theme-accent">★ {{ $stylist['average_rating'] }}</text>
                            <text class="text-sm text-theme-on-surface-variant/60">({{ $stylist['feedback_count'] }})</text>
                        </row>
                    @endif
                </column>

                <column
                    @press="toggleFavorite({{ $stylist['id'] }})"
                    class="w-[44] h-[44] rounded-full bg-theme-primary/15 items-center justify-center"
                    a11y-label="Remove {{ $stylist['name'] }} from favorites"
                >
                    <text class="text-xl text-theme-primary">♥</text>
                </column>
            </row>
        @empty
            @unless ($error)
                <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center gap-5">
                    <text class="text-theme-on-surface-variant">You have not favorited a stylist yet.</text>
                    <button label="Find your stylist" variant="primary" @press="goToBook" class="w-full" />
                </column>
            @endunless
        @endforelse
    </column>
</scroll-view>
