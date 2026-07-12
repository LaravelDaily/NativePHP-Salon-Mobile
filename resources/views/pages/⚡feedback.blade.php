<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Livewire\Component;

new class extends Component
{
    /** @var array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}|null */
    public ?array $visit = null;

    public int $rating = 0;

    public string $comment = '';

    public bool $submitted = false;

    public ?string $error = null;

    public function mount(ApiClient $api, string $appointment): void
    {
        try {
            /** @var array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}|null $visit */
            $visit = $api->get("appointments/{$appointment}")['data'] ?? null;
            $this->visit = $visit;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function submit(ApiClient $api): void
    {
        if ($this->visit === null) {
            return;
        }

        $validated = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.between' => 'Choose a rating from one to five stars.',
        ]);

        $this->error = null;

        try {
            $api->post("appointments/{$this->visit['id']}/feedback", [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] === '' ? null : $validated['comment'],
            ]);
        } catch (ApiException $exception) {
            foreach ($exception->errors as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            if ($exception->errors === []) {
                $this->error = $exception->getMessage();
            }

            return;
        }

        $this->submitted = true;
    }
};
?>

<div class="screen">
    <native:top-bar title="Salon Feedback" :show-navigation-icon="true" />

    @if ($submitted)
        <div class="card mt-6 p-8 text-center" data-testid="feedback-thanks">
            <p class="text-4xl text-accent">★★★★★</p>
            <h2 class="mt-4 text-2xl font-semibold">Thank you</h2>
            <p class="mt-2 text-content-muted">Your feedback helps StyleHub keep every visit sharp.</p>
            <a href="{{ route('appointments') }}" wire:navigate class="btn-accent mt-6">Back to history</a>
        </div>
    @elseif ($visit)
        <h2 class="text-2xl font-semibold tracking-tight">How was your visit?</h2>
        <p class="mt-2 text-content-muted">
            {{ $visit['service']['name'] }} with {{ $visit['specialist']['name'] }}
        </p>

        <form wire:submit="submit" class="mt-8 space-y-6">
            <fieldset>
                <legend class="text-sm font-medium">Your rating</legend>
                <div class="mt-3 flex gap-2" role="radiogroup" aria-label="Rating">
                    @foreach (range(1, 5) as $star)
                        <button
                            type="button"
                            wire:key="rating-{{ $star }}"
                            wire:click="$set('rating', {{ $star }})"
                            class="flex size-12 items-center justify-center rounded-full bg-surface-raised text-2xl {{ $rating >= $star ? 'text-accent' : 'text-content-subtle' }}"
                            aria-label="{{ $star }} star rating"
                            aria-pressed="{{ $rating === $star ? 'true' : 'false' }}"
                        >★</button>
                    @endforeach
                </div>
                @error('rating') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium">Anything else? <span class="text-content-subtle">Optional</span></span>
                <textarea
                    wire:model="comment"
                    rows="5"
                    maxlength="1000"
                    class="mt-3 w-full rounded-card border border-hairline bg-surface px-4 py-3 text-content outline-none focus:border-accent"
                    placeholder="Tell us what stood out..."
                ></textarea>
                @error('comment') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                @error('appointment') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </label>

            @if ($error)
                <p class="rounded-2xl border border-danger/40 px-4 py-3 text-sm text-danger">{{ $error }}</p>
            @endif

            <button type="submit" wire:loading.attr="disabled" class="btn-accent disabled:opacity-50">
                <span wire:loading.remove wire:target="submit">Submit feedback</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </button>
        </form>
    @elseif ($error)
        <div class="card mt-6 p-6 text-center">
            <p class="text-content-muted">{{ $error }}</p>
        </div>
    @endif
</div>
