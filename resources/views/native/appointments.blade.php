@php
    $initials = fn (string $name): string => Str::of($name)
        ->explode(' ')
        ->take(2)
        ->map(fn (string $part): string => Str::substr($part, 0, 1))
        ->implode('');
@endphp

<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        <text class="text-2xl font-semibold text-theme-on-background">Upcoming Appointments</text>

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

        @forelse ($upcoming as $appointment)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4">
                <row class="w-full items-center gap-3">
                    @if ($appointment['specialist']['photo_url'])
                        <image src="{{ $appointment['specialist']['photo_url'] }}" :width="48" :height="48" class="rounded-full" />
                    @else
                        <column class="w-[48] h-[48] rounded-full bg-theme-surface-variant items-center justify-center">
                            <text class="text-sm font-semibold text-theme-on-surface">{{ $initials($appointment['specialist']['name']) }}</text>
                        </column>
                    @endif

                    <column class="flex-1 gap-1">
                        <text class="font-semibold text-theme-on-surface">{{ $appointment['service']['name'] }}</text>
                        <text class="text-sm text-theme-on-surface-variant">with {{ $appointment['specialist']['name'] }}</text>
                        <text class="text-sm text-theme-on-surface">{{ $appointment['date_label'] }} · {{ $appointment['time_label'] }}</text>
                    </column>
                </row>

                <button label="Reschedule Appointment" variant="primary" @press="reschedule({{ $appointment['id'] }})" class="w-full" />
                <button label="Cancel Appointment" variant="destructive" @press="confirmCancel({{ $appointment['id'] }})" class="w-full" />
            </column>
        @empty
            @unless ($error)
                <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center gap-5">
                    <text class="text-theme-on-surface-variant">You have no upcoming appointments.</text>
                    <button label="Book an appointment" variant="primary" @press="goToBook" class="w-full" />
                </column>
            @endunless
        @endforelse

        <row class="w-full items-end gap-2 pt-6">
            <text class="text-2xl font-semibold text-theme-on-background">History</text>
            @if ($past !== [])
                <text class="text-sm text-theme-on-surface-variant pb-1">{{ count($past) }} past</text>
            @endif
        </row>

        @forelse ($past as $appointment)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-2">
                <row class="w-full items-center gap-3">
                    <column class="flex-1 gap-1">
                        <text class="font-medium text-theme-on-surface">{{ $appointment['service']['name'] }}</text>
                        <text class="text-sm text-theme-on-surface-variant">with {{ $appointment['specialist']['name'] }} · {{ $appointment['date_label'] }}</text>
                    </column>

                    <column class="bg-theme-surface-variant rounded-full px-4 py-2">
                        <text class="text-xs {{ $appointment['status'] === 'cancelled' ? 'text-theme-destructive' : 'text-theme-on-surface-variant' }}">{{ $appointment['status_label'] }}</text>
                    </column>
                </row>

                @if ($appointment['status'] === 'completed' && ($appointment['feedback'] ?? null) === null)
                    <text class="text-sm font-medium text-theme-primary pt-2" @press="rateVisit({{ $appointment['id'] }})">Rate this visit →</text>
                @elseif (($appointment['feedback'] ?? null) !== null)
                    <row class="gap-0 pt-1">
                        <text class="text-sm text-theme-accent">{{ str_repeat('★', $appointment['feedback']['rating']) }}</text>
                        @if ($appointment['feedback']['rating'] < 5)
                            <text class="text-sm text-theme-on-surface-variant/60">{{ str_repeat('★', 5 - $appointment['feedback']['rating']) }}</text>
                        @endif
                    </row>
                @endif
            </column>
        @empty
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-8 items-center">
                <text class="text-theme-on-surface-variant">Your past visits will appear here.</text>
            </column>
        @endforelse
    </column>
</scroll-view>
