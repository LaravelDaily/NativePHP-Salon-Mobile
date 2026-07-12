<?php

namespace App\Services;

use App\Contracts\TokenStore;
use Illuminate\Support\Facades\Session;

/**
 * The token store used when the app is not running inside the native shell:
 * browser development and the test suite. Not for production devices — the
 * device build uses SecureStorageTokenStore.
 *
 * The session is resolved on each call rather than captured in the constructor,
 * because this store is a singleton that outlives any one request's session.
 */
class SessionTokenStore implements TokenStore
{
    public const KEY = 'stylehub.auth_token';

    public function get(): ?string
    {
        $token = Session::get(self::KEY);

        return is_string($token) ? $token : null;
    }

    public function put(string $token): void
    {
        Session::put(self::KEY, $token);
    }

    public function forget(): void
    {
        Session::forget(self::KEY);
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }
}
