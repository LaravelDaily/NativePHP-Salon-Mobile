<?php

namespace App\Contracts;

/**
 * Where the customer's API token lives between app launches.
 *
 * On a device this is the platform keystore (see SecureStorageTokenStore). In a
 * desktop browser — used for quick UI work — there is no keystore, so the
 * session stands in. Nothing else in the app needs to know which is in play.
 */
interface TokenStore
{
    public function get(): ?string;

    public function put(string $token): void;

    public function forget(): void;

    public function has(): bool;
}
