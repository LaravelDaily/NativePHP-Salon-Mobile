<?php

namespace App\Providers;

use App\Contracts\TokenStore;
use App\Services\ApiClient;
use App\Services\SecureStorageTokenStore;
use App\Services\SessionTokenStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use ReflectionFunction;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TokenStore::class, function (): TokenStore {
            // On a device the token belongs in the platform keystore. In a
            // browser (local UI work, tests) there is no keystore, so the
            // session stands in — see App\Contracts\TokenStore.
            return $this->runningOnDevice()
                ? new SecureStorageTokenStore
                : new SessionTokenStore;
        });

        $this->app->singleton(ApiClient::class, function (): ApiClient {
            $host = config('services.salon_api.host');

            return new ApiClient(
                baseUrl: (string) config('services.salon_api.url'),
                hostHeader: is_string($host) && $host !== '' ? $host : null,
                timeout: (int) config('services.salon_api.timeout'),
                tokens: $this->app->make(TokenStore::class),
            );
        });
    }

    /**
     * Whether the app is running on a device, where the native bridge is real.
     *
     * Testing `function_exists('nativephp_call')` is not enough: NativePHP also
     * defines that function on the developer's machine, as a PHP fallback that
     * relays calls to a connected device. So the function's existence proves
     * nothing — but its origin does. On a device it is supplied by NativePHP's
     * C extension; anywhere else it is ordinary userland PHP.
     */
    private function runningOnDevice(): bool
    {
        return function_exists('nativephp_call')
            && (new ReflectionFunction('nativephp_call'))->isInternal();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
