<?php

namespace App\NativeComponents\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Validator;

/**
 * Field-level error handling for native screens.
 *
 * Livewire carried a per-request error bag; a NativeComponent lives for the
 * whole screen, so validation and API errors are held in plain state instead:
 * `$fieldErrors` for messages that belong on an input, `$error` for the
 * screen-wide banner.
 */
trait HandlesApiErrors
{
    /** @var array<string, string> input name → message */
    public array $fieldErrors = [];

    /** Screen-wide error banner, for failures that belong to no single field. */
    public ?string $error = null;

    /**
     * Validate input against Laravel rules, filling `$fieldErrors` on failure.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     */
    protected function passesValidation(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $this->fieldErrors = array_map(
                fn (array $messages): string => $messages[0],
                $validator->errors()->messages(),
            );

            return false;
        }

        $this->fieldErrors = [];

        return true;
    }

    /**
     * File an API failure where it belongs: field problems onto their inputs
     * (renamed where the app's property differs from the API's key), anything
     * else onto the screen-wide banner.
     *
     * @param  array<string, string>  $renames  API field → input name
     */
    protected function addApiErrors(ApiException $exception, array $renames = []): void
    {
        foreach ($exception->errors as $field => $messages) {
            $this->fieldErrors[$renames[$field] ?? $field] = $messages[0];
        }

        if ($exception->errors === []) {
            $this->error = $exception->getMessage();
        }
    }

    protected function fieldError(string $field): ?string
    {
        return $this->fieldErrors[$field] ?? null;
    }
}
