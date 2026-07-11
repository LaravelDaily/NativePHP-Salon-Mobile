<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * A failed call to the salon API, carrying a message that is safe to show to
 * the user. Screens catch this and render it rather than blowing up.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors  Validation errors, keyed by field.
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * The API could not be reached at all: no network, or the wrong base URL.
     */
    public static function unreachable(Throwable $previous): self
    {
        return new self(
            "Can't reach StyleHub right now. Check your connection and try again.",
            previous: $previous,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();

        return new self(
            match (true) {
                $status === 401 || $status === 419 => 'Your session has expired. Please sign in again.',
                $status === 403 => "You don't have permission to do that.",
                $status === 404 => "We couldn't find what you were looking for.",
                $status === 422 => self::firstValidationError($response) ?? 'Please check the details and try again.',
                $status === 429 => "You've tried that too many times. Please wait a moment.",
                $status >= 500 => 'StyleHub is having trouble right now. Please try again shortly.',
                default => 'Something went wrong. Please try again.',
            },
            status: $status,
            errors: self::validationErrors($response),
        );
    }

    public function isUnauthenticated(): bool
    {
        return $this->status === 401 || $this->status === 419;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function validationErrors(Response $response): array
    {
        /** @var array<string, array<int, string>> $errors */
        $errors = $response->json('errors') ?? [];

        return $errors;
    }

    private static function firstValidationError(Response $response): ?string
    {
        $first = collect(self::validationErrors($response))->flatten()->first();

        return is_string($first) ? $first : $response->json('message');
    }
}
