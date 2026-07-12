<?php

namespace App\Services;

use App\Contracts\TokenStore;
use Native\Mobile\Facades\SecureStorage;

/**
 * The on-device token store: the iOS Keychain and Android's EncryptedShared-
 * Preferences, reached through the nativephp/mobile-secure-storage plugin.
 *
 * Requires that plugin to be installed and registered; without it the native
 * bridge is absent and every call quietly returns null/false, which is why the
 * container only binds this store when running inside the native shell.
 */
class SecureStorageTokenStore implements TokenStore
{
    private const KEY = 'stylehub_auth_token';

    public function get(): ?string
    {
        return SecureStorage::get(self::KEY);
    }

    public function put(string $token): void
    {
        SecureStorage::set(self::KEY, $token);
    }

    public function forget(): void
    {
        SecureStorage::delete(self::KEY);
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }
}
