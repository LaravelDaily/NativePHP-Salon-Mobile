<?php

use App\Contracts\TokenStore;
use App\Services\SecureStorageTokenStore;
use App\Services\SessionTokenStore;

it('uses the session store off-device', function () {
    // NativePHP defines a userland nativephp_call() on the dev machine, so the
    // container must not mistake this for a device and reach for a keystore
    // that is not there — that failure is silent, and signs nobody in.
    expect(app(TokenStore::class))->toBeInstanceOf(SessionTokenStore::class);
});

it('round-trips a token', function () {
    $store = app(TokenStore::class);

    expect($store->has())->toBeFalse()
        ->and($store->get())->toBeNull();

    $store->put('issued-token');

    expect($store->get())->toBe('issued-token')
        ->and($store->has())->toBeTrue();

    $store->forget();

    expect($store->get())->toBeNull()
        ->and($store->has())->toBeFalse();
});

it('keeps both stores interchangeable', function () {
    expect(class_implements(SecureStorageTokenStore::class))->toContain(TokenStore::class)
        ->and(class_implements(SessionTokenStore::class))->toContain(TokenStore::class);
});
