<?php

namespace App\NativeComponents;

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Stylists extends NativeComponent
{
    /** @var array<int, array{id: int, name: string, title: string|null, photo_url: string|null, average_rating: float|int|null, feedback_count: int, is_favorite: bool, ...}> */
    public array $stylists = [];

    public ?string $error = null;

    public ?string $notice = null;

    public function navTitle(): string
    {
        return 'My Stylists';
    }

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->error = null;

        try {
            /** @var array<int, array{id: int, name: string, title: string|null, photo_url: string|null, average_rating: float|int|null, feedback_count: int, is_favorite: bool, ...}> $stylists */
            $stylists = app(ApiClient::class)->get('me/stylists')['data'] ?? [];
            $this->stylists = $stylists;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function toggleFavorite(int $specialistId): void
    {
        $this->error = null;

        $stylist = collect($this->stylists)->firstWhere('id', $specialistId);

        if ($stylist === null) {
            return;
        }

        try {
            app(ApiClient::class)->delete("specialists/{$specialistId}/favorite");
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->stylists = array_values(array_filter(
            $this->stylists,
            fn (array $entry): bool => $entry['id'] !== $specialistId,
        ));
        $this->notice = "{$stylist['name']} was removed from My Stylists.";
    }

    public function goToBook(): void
    {
        $this->navigate('/book');
    }

    public function render(): View
    {
        return view('native.stylists');
    }
}
