<?php

namespace App\Services;

use App\Contracts\TokenStore;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The app's single door to the salon API.
 *
 * This app is API-first: on-device SQLite holds only local state, and every
 * piece of salon data comes through here. Callers pass version-less paths
 * ("salon", "appointments") — the API version is applied in one place.
 *
 * Failures are translated into an {@see ApiException} carrying a user-safe
 * message, so screens never have to interpret raw HTTP status codes.
 */
class ApiClient
{
    private const API_PREFIX = 'api/v1';

    private ?string $token = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $hostHeader,
        private readonly int $timeout,
        private readonly ?TokenStore $tokens = null,
    ) {}

    /**
     * Authenticate subsequent requests with an explicit token, overriding the
     * stored one. Used during sign-in, before the token has been persisted.
     */
    public function withToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * The token to send: an explicitly-set one, otherwise whatever the customer
     * is signed in with.
     */
    private function currentToken(): ?string
    {
        return $this->token ?? $this->tokens?->get();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send(fn (PendingRequest $request): Response => $request->get($path, $query));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function post(string $path, array $data = []): array
    {
        return $this->send(fn (PendingRequest $request): Response => $request->post($path, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function patch(string $path, array $data = []): array
    {
        return $this->send(fn (PendingRequest $request): Response => $request->patch($path, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function delete(string $path, array $data = []): array
    {
        return $this->send(fn (PendingRequest $request): Response => $request->delete($path, $data));
    }

    /**
     * @param  Closure(PendingRequest): Response  $send
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    private function send(Closure $send): array
    {
        try {
            $response = $send($this->request());
        } catch (ConnectionException $exception) {
            throw ApiException::unreachable($exception);
        }

        if ($response->failed()) {
            throw ApiException::fromResponse($response);
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        return $body;
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim($this->baseUrl, '/').'/'.self::API_PREFIX)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson();

        // The Android emulator reaches the host machine at 10.0.2.2 but cannot
        // resolve Herd's .test domains, so we address the host directly and let
        // this Host header tell Herd which site to serve.
        if ($this->hostHeader !== null && $this->hostHeader !== '') {
            $request->withHeaders(['Host' => $this->hostHeader]);
        }

        $token = $this->currentToken();

        if ($token !== null) {
            $request->withToken($token);
        }

        return $request;
    }
}
