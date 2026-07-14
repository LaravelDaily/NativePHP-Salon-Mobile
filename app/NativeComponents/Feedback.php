<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\NativeComponents\Concerns\HandlesApiErrors;
use App\Services\ApiClient;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Feedback extends NativeComponent
{
    use HandlesApiErrors;

    /** @var array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}|null */
    public ?array $visit = null;

    public int $rating = 0;

    public string $comment = '';

    public bool $submitted = false;

    public bool $saving = false;

    public function navTitle(): string
    {
        return 'Salon Feedback';
    }

    public function mount(): void
    {
        $appointment = $this->param('appointment');

        try {
            /** @var array{id: int, status: string, feedback: array<string, mixed>|null, service: array{name: string, ...}, specialist: array{name: string, ...}, ...}|null $visit */
            $visit = app(ApiClient::class)->get("appointments/{$appointment}")['data'] ?? null;
            $this->visit = $visit;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function rate(int $stars): void
    {
        $this->rating = $stars;
    }

    public function submit(): void
    {
        if ($this->visit === null) {
            return;
        }

        if (! $this->passesValidation(
            ['rating' => $this->rating, 'comment' => $this->comment],
            [
                'rating' => ['required', 'integer', 'between:1,5'],
                'comment' => ['nullable', 'string', 'max:1000'],
            ],
            ['rating.between' => 'Choose a rating from one to five stars.'],
        )) {
            return;
        }

        $this->error = null;
        $this->saving = true;
        $this->publishFinalState();

        try {
            app(ApiClient::class)->post("appointments/{$this->visit['id']}/feedback", [
                'rating' => $this->rating,
                'comment' => $this->comment === '' ? null : $this->comment,
            ]);
        } catch (ApiException $exception) {
            $this->addApiErrors($exception);
            $this->saving = false;

            return;
        }

        $this->saving = false;
        $this->submitted = true;
    }

    public function backToHistory(): void
    {
        $this->navigate('/appointments');
    }

    public function render(): View
    {
        return view('native.feedback');
    }
}
