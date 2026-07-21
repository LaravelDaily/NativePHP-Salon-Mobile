<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Illuminate\View\View;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

#[Lazy]
class Home extends NativeComponent
{
    /** @var array<string, mixed>|null */
    public ?array $salon = null;

    /** @var array<string, mixed>|null */
    public ?array $next = null;

    /** @var array<string, mixed>|null */
    public ?array $pendingFeedback = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->error = null;

        $responses = app(ApiClient::class)->getMany([
            'salon' => ['path' => 'salon'],
            'upcoming' => ['path' => 'appointments', 'query' => ['filter' => 'upcoming', 'per_page' => 1]],
            'reviewable' => ['path' => 'appointments', 'query' => ['filter' => 'reviewable', 'per_page' => 1]],
        ]);

        if ($responses['salon']['error'] instanceof ApiException) {
            $this->salon = null;
            $this->error = $responses['salon']['error']->getMessage();

            return;
        }

        $this->salon = $responses['salon']['data']['data'] ?? null;
        $this->next = $responses['upcoming']['data']['data'][0] ?? null;
        $this->pendingFeedback = $responses['reviewable']['data']['data'][0] ?? null;
    }

    public function bookAppointment(): void
    {
        $this->navigate('/book');
    }

    public function viewAppointments(): void
    {
        $this->navigate('/appointments');
    }

    public function giveFeedback(): void
    {
        if ($this->pendingFeedback === null) {
            return;
        }

        $this->navigate("/appointments/{$this->pendingFeedback['id']}/feedback");
    }

    /**
     * The frame painted the instant this screen is pushed, before mount()
     * goes off to the API. It is the real screen with every value greyed
     * out, so the data lands on shapes that are already in place.
     */
    protected function placeholder(): View
    {
        return view('native.skeleton.home');
    }

    public function render(): View
    {
        return view('native.home');
    }
}
