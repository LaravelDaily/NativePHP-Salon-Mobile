<?php

use App\Exceptions\ApiException;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function apiClient(?string $host = null, string $url = 'http://10.0.2.2'): ApiClient
{
    return new ApiClient(baseUrl: $url, hostHeader: $host, timeout: 5);
}

it('prefixes requests with the API version', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    apiClient()->get('salon');

    Http::assertSent(fn ($request): bool => $request->url() === 'http://10.0.2.2/api/v1/salon');
});

it('sends a Host header so Herd routes the request from the emulator', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    apiClient(host: 'salon-api.test')->get('salon');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Host', 'salon-api.test'));
});

it('addresses the API directly when no Host header is configured', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    apiClient(host: null, url: 'http://salon-api.test')->get('salon');

    Http::assertSent(fn ($request): bool => $request->url() === 'http://salon-api.test/api/v1/salon');
});

it('returns the decoded body', function () {
    Http::fake(['*' => Http::response(['data' => ['name' => 'StyleHub']])]);

    expect(apiClient()->get('salon'))->toBe(['data' => ['name' => 'StyleHub']]);
});

it('sends a bearer token once one is set', function () {
    Http::fake(['*' => Http::response([])]);

    apiClient()->withToken('secret-token')->get('me');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer secret-token'));
});

it('translates an unreachable API into a friendly error', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    expect(fn () => apiClient()->get('salon'))
        ->toThrow(ApiException::class, "Can't reach StyleHub right now. Check your connection and try again.");
});

it('translates HTTP failures into friendly errors', function (int $status, string $message) {
    Http::fake(['*' => Http::response(status: $status)]);

    expect(fn () => apiClient()->get('salon'))->toThrow(ApiException::class, $message);
})->with([
    [401, 'Your session has expired. Please sign in again.'],
    [403, "You don't have permission to do that."],
    [404, "We couldn't find what you were looking for."],
    [429, "You've tried that too many times. Please wait a moment."],
    [500, 'StyleHub is having trouble right now. Please try again shortly.'],
]);

it('surfaces the first validation error from a 422', function () {
    Http::fake(['*' => Http::response([
        'message' => 'The given data was invalid.',
        'errors' => ['phone' => ['That phone number is already taken.']],
    ], 422)]);

    try {
        apiClient()->post('auth/register', ['phone' => '123']);
    } catch (ApiException $exception) {
        expect($exception->getMessage())->toBe('That phone number is already taken.')
            ->and($exception->status)->toBe(422)
            ->and($exception->errors)->toBe(['phone' => ['That phone number is already taken.']]);

        return;
    }

    $this->fail('Expected an ApiException.');
});

it('flags unauthenticated responses so screens can redirect to sign-in', function () {
    Http::fake(['*' => Http::response(status: 401)]);

    try {
        apiClient()->get('me');
    } catch (ApiException $exception) {
        expect($exception->isUnauthenticated())->toBeTrue();

        return;
    }

    $this->fail('Expected an ApiException.');
});
