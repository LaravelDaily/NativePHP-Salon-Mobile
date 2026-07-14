<?php

use App\Contracts\TokenStore;
use App\Exceptions\ApiException;
use App\NativeComponents\Profile;
use App\Services\CustomerAuth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->tokens = app(TokenStore::class);
    $this->auth = app(CustomerAuth::class);
});

it('is signed out when no token is held', function () {
    expect($this->auth->check())->toBeFalse()
        ->and($this->auth->user())->toBeNull();
});

it('stores the token and the user handed back by the auth endpoint', function () {
    Http::fake();

    $this->auth->signIn('issued-token', ['id' => 1, 'name' => 'John Doe']);

    expect($this->tokens->get())->toBe('issued-token')
        ->and($this->auth->check())->toBeTrue()
        ->and($this->auth->user()['name'])->toBe('John Doe');

    // The user was already supplied, so there was no need to ask /me for it.
    Http::assertNothingSent();
});

it('fetches the customer from /me when signing in without one', function () {
    Http::fake(['*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe']])]);

    $this->auth->signIn('issued-token');

    expect($this->auth->user()['name'])->toBe('John Doe');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer issued-token'));
});

it('only asks /me once per request', function () {
    Http::fake(['*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe']])]);
    $this->tokens->put('issued-token');

    $this->auth->user();
    $this->auth->user();

    Http::assertSentCount(1);
});

it('discards a token the API has revoked', function () {
    Http::fake(['*/api/v1/me' => Http::response(status: 401)]);
    $this->tokens->put('revoked-token');

    expect($this->auth->user())->toBeNull()
        ->and($this->tokens->has())->toBeFalse();
});

it('does not sign the customer out just because the API is having a bad day', function () {
    Http::fake(['*/api/v1/me' => Http::response(status: 500)]);
    $this->tokens->put('good-token');

    expect(fn () => $this->auth->user())->toThrow(ApiException::class);
    expect($this->tokens->get())->toBe('good-token');
});

it('revokes the token server-side on sign out', function () {
    Http::fake(['*/api/v1/auth/logout' => Http::response(['message' => 'Signed out.'])]);
    $this->tokens->put('issued-token');

    $this->auth->signOut();

    expect($this->tokens->has())->toBeFalse()
        ->and($this->auth->user())->toBeNull();

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), 'auth/logout'));
});

it('still forgets the token locally when the API cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('refused'));
    $this->tokens->put('issued-token');

    $this->auth->signOut();

    expect($this->tokens->has())->toBeFalse();
});

it('signs out from the profile screen and returns to welcome', function () {
    Http::fake([
        '*/api/v1/me' => Http::response(['data' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']]),
        '*/api/v1/me/stylists' => Http::response(['data' => []]),
        '*/api/v1/salon' => Http::response(['data' => ['average_rating' => null, 'feedback_count' => 0]]),
        '*/api/v1/auth/logout' => Http::response(['message' => 'Signed out.']),
    ]);
    $this->tokens->put('issued-token');

    Native::test(Profile::class)
        ->assertSee('John Doe')
        ->press('signOut')
        ->assertReplacedWith('/welcome');

    expect($this->tokens->has())->toBeFalse();
});
