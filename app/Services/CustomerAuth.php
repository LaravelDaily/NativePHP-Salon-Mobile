<?php

namespace App\Services;

use App\Contracts\TokenStore;
use App\Exceptions\ApiException;

/**
 * The app's view of who is signed in.
 *
 * There is no local user table: the token is the whole of the client-side auth
 * state, and the customer's details are whatever `GET /me` last returned. That
 * keeps the device honest — a token revoked server-side signs the app out on
 * its next request rather than trusting stale local data.
 */
class CustomerAuth
{
    /** @var array<string, mixed>|null */
    private ?array $user = null;

    private bool $loaded = false;

    public function __construct(
        private readonly ApiClient $api,
        private readonly TokenStore $tokens,
    ) {}

    /**
     * Whether a token is held. This does not prove the token is still valid —
     * only the API can say that, which {@see user()} asks it.
     */
    public function check(): bool
    {
        return $this->tokens->has();
    }

    /**
     * Adopt a freshly issued token and load the customer behind it.
     *
     * @param  array<string, mixed>|null  $user  The user the auth endpoint already returned, if any.
     *
     * @throws ApiException
     */
    public function signIn(string $token, ?array $user = null): void
    {
        $this->tokens->put($token);

        $this->user = $user;
        $this->loaded = $user !== null;

        if ($user === null) {
            $this->user();
        }
    }

    /**
     * The signed-in customer, or null. Fetched once per request.
     *
     * A token the API rejects is discarded here, so a revoked or expired token
     * cannot leave the app in a half-signed-in state.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }

        $this->loaded = true;

        if (! $this->tokens->has()) {
            return $this->user = null;
        }

        try {
            /** @var array<string, mixed>|null $user */
            $user = $this->api->get('me')['data'] ?? null;

            return $this->user = $user;
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated()) {
                $this->tokens->forget();

                return $this->user = null;
            }

            throw $exception;
        }
    }

    /**
     * Sign out. The local token is cleared even if the API cannot be reached —
     * the customer asked to sign out, so the device must forget them either way.
     */
    public function signOut(): void
    {
        try {
            if ($this->tokens->has()) {
                $this->api->post('auth/logout');
            }
        } catch (ApiException) {
            // Already invalid, or offline. Nothing left to revoke.
        } finally {
            $this->tokens->forget();
            $this->user = null;
            $this->loaded = true;
        }
    }
}
