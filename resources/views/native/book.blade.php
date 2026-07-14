@php
    $serviceIcons = ['scissors' => '✂️', 'razor' => '🪒', 'sparkles' => '✨', 'palette' => '🎨'];
    $isReschedule = $rescheduling !== null;
    $initials = fn (string $name): string => Str::of($name)
        ->explode(' ')
        ->take(2)
        ->map(fn (string $part): string => Str::substr($part, 0, 1))
        ->implode('');
@endphp

<scroll-view class="bg-theme-background">
    <column class="w-full p-5 gap-4" safe-area>
        @if ($booked)
            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-6 items-center gap-2 mt-6">
                <text class="text-4xl text-theme-primary">✓</text>

                <text class="text-2xl font-semibold text-theme-on-surface pt-2">
                    {{ $isReschedule ? 'Appointment moved' : 'Booking confirmed' }}
                </text>

                <text class="text-theme-on-surface-variant">{{ $booked['service']['name'] }} with {{ $booked['specialist']['name'] }}</text>
                <text class="text-theme-on-surface">{{ $booked['date_label'] }} at {{ $booked['time_label'] }}</text>

                <button label="My appointments" variant="primary" size="lg" @press="viewAppointments" class="w-full mt-4" />

                @unless ($isReschedule)
                    <text class="text-sm text-theme-on-surface-variant pt-3" @press="bookAnother">Book another</text>
                @endunless
            </column>
        @else
            <text class="text-2xl font-semibold text-theme-on-background">
                {{ $isReschedule ? 'Pick a new time' : 'New Appointment' }}
            </text>

            @if ($error)
                <column class="w-full border border-theme-destructive/40 rounded-2xl px-4 py-3">
                    <text class="text-sm text-theme-destructive">{{ $error }}</text>
                </column>
            @endif

            @if ($isReschedule)
                <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-1">
                    <text class="text-xs font-semibold text-theme-on-surface-variant">RESCHEDULING</text>
                    <text class="font-semibold text-theme-on-surface pt-1">{{ $rescheduling['service']['name'] }}</text>
                    <text class="text-sm text-theme-on-surface-variant">
                        Currently {{ $rescheduling['date_label'] }} at {{ $rescheduling['time_label'] }}
                    </text>
                </column>
            @else
                <text class="text-lg font-semibold text-theme-on-background pt-2">Choose Service</text>

                <column class="w-full gap-2">
                    @foreach ($services as $service)
                        <row
                            @press="selectService({{ $service['id'] }})"
                            class="w-full items-center gap-3 px-4 py-3 bg-theme-surface rounded-3xl border {{ $service['id'] === $serviceId ? 'border-theme-primary' : 'border-theme-outline' }}"
                        >
                            <text class="text-lg">{{ $serviceIcons[$service['icon']] ?? '💈' }}</text>

                            <column class="flex-1 gap-1">
                                <text class="font-medium text-theme-on-surface">{{ $service['name'] }}</text>
                                <text class="text-xs text-theme-on-surface-variant">
                                    {{ $service['duration_minutes'] }} min · {{ $service['price_formatted'] }}
                                </text>
                            </column>

                            @if ($service['id'] === $serviceId)
                                <text class="text-theme-primary">✓</text>
                            @endif
                        </row>
                    @endforeach
                </column>
            @endif

            <row class="w-full items-center justify-between pt-4">
                <text class="text-lg font-semibold text-theme-on-background">{{ $this->monthLabel() }}</text>

                <row class="gap-2">
                    <column @press="previousWeek" class="bg-theme-surface-variant rounded-full px-4 py-2 {{ $weekOffset === 0 ? 'opacity-40' : '' }}">
                        <text class="text-theme-on-surface-variant">‹</text>
                    </column>
                    <column @press="nextWeek" class="bg-theme-surface-variant rounded-full px-4 py-2">
                        <text class="text-theme-on-surface-variant">›</text>
                    </column>
                </row>
            </row>

            <scroll-view horizontal class="w-full">
                <row class="gap-2">
                    @foreach ($this->days() as $day)
                        <column
                            @press="selectDate('{{ $day->toDateString() }}')"
                            class="items-center px-4 py-2 rounded-full {{ $day->toDateString() === $date ? 'bg-theme-primary' : 'bg-theme-surface-variant' }}"
                        >
                            <text class="text-xs {{ $day->toDateString() === $date ? 'text-theme-on-primary' : 'text-theme-on-surface-variant' }}">{{ $day->format('D') }}</text>
                            <text class="text-base font-semibold {{ $day->toDateString() === $date ? 'text-theme-on-primary' : 'text-theme-on-surface' }}">{{ $day->format('d') }}</text>
                        </column>
                    @endforeach
                </row>
            </scroll-view>

            <text class="text-lg font-semibold text-theme-on-background pt-4">Choose Specialist</text>

            @if ($availability === [])
                <text class="text-sm text-theme-on-surface-variant">No specialists offer this service yet.</text>
            @else
                <scroll-view horizontal class="w-full">
                    <row class="gap-4">
                        @foreach ($availability as $entry)
                            @php($specialist = $entry['specialist'])

                            <column class="items-center w-[80] gap-1 {{ $entry['slot_count'] === 0 ? 'opacity-40' : '' }}">
                                <column
                                    @press="selectSpecialist({{ $specialist['id'] }})"
                                    class="w-[64] h-[64] rounded-full bg-theme-surface-variant items-center justify-center {{ $specialist['id'] === $specialistId ? 'border-2 border-theme-primary' : '' }}"
                                >
                                    @if ($specialist['photo_url'])
                                        <image src="{{ $specialist['photo_url'] }}" :width="60" :height="60" class="rounded-full" />
                                    @else
                                        <text class="font-semibold text-theme-on-surface">{{ $initials($specialist['name']) }}</text>
                                    @endif
                                </column>

                                <text class="text-xs text-theme-on-surface-variant">{{ $specialist['name'] }}</text>

                                <text
                                    @press="toggleFavorite({{ $specialist['id'] }})"
                                    class="text-sm {{ ($specialist['is_favorite'] ?? false) ? 'text-theme-primary' : 'text-theme-on-surface-variant/60' }}"
                                    a11y-label="{{ ($specialist['is_favorite'] ?? false) ? 'Remove' : 'Add' }} {{ $specialist['name'] }} {{ ($specialist['is_favorite'] ?? false) ? 'from' : 'to' }} favorites"
                                >♥</text>
                            </column>
                        @endforeach
                    </row>
                </scroll-view>
            @endif

            @forelse ($this->slotsByPeriod() as $period)
                <text class="text-lg font-semibold text-theme-on-background pt-4">{{ $period['label'] }}</text>

                <column class="w-full gap-2">
                    @foreach (collect($period['slots'])->chunk(3) as $slotRow)
                        <row class="w-full gap-2">
                            @foreach ($slotRow as $slot)
                                <column
                                    @press="selectTime('{{ $slot['starts_at'] }}')"
                                    class="flex-1 items-center rounded-full px-4 py-2 {{ $slot['starts_at'] === $startsAt ? 'bg-theme-primary' : 'bg-theme-surface-variant' }}"
                                >
                                    <text class="text-sm {{ $slot['starts_at'] === $startsAt ? 'font-semibold text-theme-on-primary' : 'text-theme-on-surface-variant' }}">{{ $slot['label'] }}</text>
                                </column>
                            @endforeach
                        </row>
                    @endforeach
                </column>
            @empty
                <text class="text-sm text-theme-on-surface-variant pt-4">
                    No times available on this day. Try another date or specialist.
                </text>
            @endforelse

            <column class="w-full bg-theme-surface border border-theme-outline rounded-3xl p-5 gap-4 mt-4">
                <row class="w-full">
                    <column class="flex-1 gap-1">
                        <text class="text-xs font-semibold text-theme-on-surface-variant">SERVICE</text>
                        <text class="text-sm text-theme-on-surface">{{ $this->selectedService()['name'] ?? '—' }}</text>
                    </column>
                    <column class="flex-1 gap-1">
                        <text class="text-xs font-semibold text-theme-on-surface-variant">DATE</text>
                        <text class="text-sm text-theme-on-surface">{{ \Carbon\CarbonImmutable::parse($date)->format('D, j M') }}</text>
                    </column>
                </row>
                <row class="w-full">
                    <column class="flex-1 gap-1">
                        <text class="text-xs font-semibold text-theme-on-surface-variant">TIME</text>
                        <text class="text-sm text-theme-on-surface">{{ $this->timeLabel() ?? '—' }}</text>
                    </column>
                    <column class="flex-1 gap-1">
                        <text class="text-xs font-semibold text-theme-on-surface-variant">SPECIALIST</text>
                        <text class="text-sm text-theme-on-surface">{{ $this->selectedSpecialist()['name'] ?? '—' }}</text>
                    </column>
                </row>

                <button
                    :label="$saving ? 'Saving…' : ($isReschedule ? 'Confirm New Time' : 'Confirm Booking')"
                    variant="primary"
                    size="lg"
                    :disabled="! $this->canConfirm()"
                    :loading="$saving"
                    @press="confirm"
                    class="w-full mt-1"
                />
            </column>
        @endif
    </column>
</scroll-view>
