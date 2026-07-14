<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Providers\SecureStorageServiceProvider;
use Nativephp\NativeUi\NativeUIServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     * This is a security measure to prevent transitive dependencies from
     * automatically registering plugins without your explicit consent.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        // Dialog and Device need no plugin entries any more: v4 folds them
        // into core, and their facades work without a registered provider.
        return [
            SecureStorageServiceProvider::class,
            NativeUIServiceProvider::class,
        ];
    }
}
