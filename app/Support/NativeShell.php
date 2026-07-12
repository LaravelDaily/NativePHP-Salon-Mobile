<?php

namespace App\Support;

use ReflectionFunction;

/**
 * Whether the app is really running on a device.
 *
 * `function_exists('nativephp_call')` is not the answer: off-device NativePHP
 * defines a userland stand-in that relays bridge calls over TCP to a connected
 * device, so the function exists even in a browser or a test run — where nothing
 * is listening and a native dialog would never come back with an answer.
 *
 * On a device the same function is provided by NativePHP's C extension, so an
 * internal function is the honest signal. Screens use this to fall back to an
 * in-app confirmation when there is no device to ask.
 */
class NativeShell
{
    public static function isRunning(): bool
    {
        if (! function_exists('nativephp_call')) {
            return false;
        }

        return (new ReflectionFunction('nativephp_call'))->isInternal();
    }
}
