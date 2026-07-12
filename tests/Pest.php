<?php

use App\Services\SessionTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * A session holding an auth token, for tests that make real HTTP requests
 * through the RequireCustomer middleware.
 *
 * Outside the native shell the token lives in the session, and a test HTTP
 * request starts a fresh one — so it has to be seeded via `withSession()`
 * rather than written beforehand.
 *
 * @return array<string, string>
 */
function customerSession(string $token = 'test-token'): array
{
    return [SessionTokenStore::KEY => $token];
}

/**
 * Sign a customer in for Livewire component tests, which share the current
 * session rather than starting a new request.
 */
function signIn(string $token = 'test-token'): void
{
    session()->put(SessionTokenStore::KEY, $token);
}
