<scroll-view class="bg-theme-background">
    <column class="w-full p-5 pt-8 gap-6" safe-area>
        @if ($error)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 items-center gap-5 mt-6">
                <text class="text-theme-on-surface-variant text-center">{{ $error }}</text>
                <button label="Try again" variant="primary" size="lg" @press="load" class="w-full" />
            </column>
        @else
            <column class="w-full gap-1">
                <text class="text-sm text-theme-on-surface-variant">Welcome to</text>
                <text class="text-4xl font-semibold text-theme-on-background">{{ $salon['name'] ?? '' }}</text>

                @if (! empty($salon['tagline']))
                    <text class="text-theme-on-surface-variant pt-1">{{ $salon['tagline'] }}</text>
                @endif
            </column>

            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 gap-2">
                <text class="text-xs font-semibold text-theme-on-surface-variant">OPENING HOURS</text>
                <text class="text-theme-on-surface">{{ $salon['opening_summary'] ?? '' }}</text>

                @if (! empty($salon['address']['formatted']))
                    <text class="text-sm text-theme-on-surface-variant pt-2">{{ $salon['address']['formatted'] }}</text>
                @endif
            </column>

            <button label="Book an appointment" variant="primary" size="lg" @press="bookAppointment" class="w-full" />

            @if ($pendingFeedback)
                <column @press="giveFeedback" class="w-full bg-theme-surface border border-theme-primary/40 rounded-3xl p-5 gap-1">
                    <text class="text-xs font-semibold text-theme-primary">HOW DID WE DO?</text>
                    <text class="font-semibold text-theme-on-surface pt-1">Rate your {{ $pendingFeedback['service']['name'] }}</text>
                    <text class="text-sm text-theme-on-surface-variant">Tap to leave feedback for your visit with {{ $pendingFeedback['specialist']['name'] }}.</text>
                </column>
            @endif

            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 gap-1">
                <text class="text-xs font-semibold text-theme-on-surface-variant">NEXT APPOINTMENT</text>

                @if ($next)
                    <text class="text-lg font-semibold text-theme-on-surface pt-1">{{ $next['service']['name'] }}</text>
                    <text class="text-sm text-theme-on-surface-variant">with {{ $next['specialist']['name'] }}</text>
                    <text class="text-theme-on-surface pt-1">{{ $next['date_label'] }} · {{ $next['time_label'] }}</text>

                    <text class="text-sm text-theme-primary pt-3" @press="viewAppointments">View appointments</text>
                @else
                    <text class="text-theme-on-surface-variant pt-1">Your next visit will show up here.</text>
                @endif
            </column>
        @endif
    </column>
</scroll-view>
