# StyleHub — Mobile App

The customer-facing StyleHub app, built with [NativePHP Mobile](https://nativephp.com/docs/mobile/3) v3, Livewire 4 and Tailwind 4. It talks to the `salon-api` backend over HTTP; the on-device SQLite database holds only local state.

## Requirements

- PHP 8.3+, Composer, Node 20+
- Android Studio with an emulator (API 33+) — Android is the primary target
- The `salon-api` project served by Herd
- `nativephp/mobile-secure-storage` (paid plugin) — stores the auth token in the device keystore

## Authentication

The customer signs in with an SMS one-time code, or with email and password. Either way the API returns a Sanctum token, and **that token is the whole of the client-side auth state** — there is no local user table. `CustomerAuth` fetches the customer from `GET /me`, and a token the API rejects is discarded on the spot, so a revoked token cannot leave the app half-signed-in.

Where that token lives depends on where the app is running, behind the `TokenStore` contract:

| Context | Store | Backing |
|---|---|---|
| Device | `SecureStorageTokenStore` | iOS Keychain / Android EncryptedSharedPreferences |
| Browser, tests | `SessionTokenStore` | The session |

The device store needs the paid plugin:

```bash
composer require nativephp/mobile-secure-storage
php artisan native:plugin:register nativephp/mobile-secure-storage
```

> **A trap worth knowing about.** Do not decide "am I on a device?" with `function_exists('nativephp_call')`. NativePHP *also* defines that function on your dev machine, as a PHP fallback that relays calls to a connected device — so it is always true, and the app would reach for a keystore that isn't there. That failure is silent: `SecureStorage::get()` just returns `null` and nobody can sign in. The reliable test is the function's **origin** — on a device it comes from NativePHP's C extension (`ReflectionFunction::isInternal()`), everywhere else it is userland PHP. See `AppServiceProvider::runningOnDevice()`.

### Reading OTP codes in development

There is no SMS gateway locally. The API's `log` SMS driver writes the code to `salon-api`'s log (`php artisan pail`), and because `OTP_EXPOSE_CODE` defaults to the API's debug flag, the code also comes back in the API response — the OTP screen shows it inline. Never enable that in production: it would hand any caller a login for any phone number.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan native:install
```

Then build the assets and launch the app (see "Running" below).

## Talking to the API from the emulator

This is the one piece of setup that is not obvious.

The API is served by Herd at `http://salon-api.test`. Two things make that URL unusable from an Android emulator:

1. The emulator is a separate virtual device, so `localhost` means *the emulator itself*, not your Mac. The host machine is reachable at the special address **`10.0.2.2`**.
2. Herd's `.test` domains are resolved by a local DNS resolver on your Mac. The emulator does not use it, so `salon-api.test` does not resolve on the device.

Pointing at `http://10.0.2.2` alone is not enough either: Herd serves many sites from one port and picks between them using the `Host` header, so a bare IP request lands on the wrong site.

**The approach used here:** address the host machine by IP and send the site name as a `Host` header, which is exactly what Herd's nginx routes on. `ApiClient` does this whenever `SALON_API_HOST` is set:

```dotenv
SALON_API_URL=http://10.0.2.2      # the host machine, as seen from the emulator
SALON_API_HOST=salon-api.test      # the Herd site to route to
```

To run the app **in a desktop browser** instead (handy for quick UI work), point it straight at the site and drop the header:

```dotenv
SALON_API_URL=http://salon-api.test
SALON_API_HOST=
```

Alternatives considered: `herd share` gives a public HTTPS URL that works from any device without a `Host` header, but the URL changes each session. The `Host`-header approach needs no tunnel and no extra account, so it is the default.

Note that Herd serves this site over **http**, not https — which conveniently means there is no TLS certificate for the emulator to distrust.

## Running

Always pass the platform when building assets:

```bash
npm run build -- --mode=android
php artisan native:run android
```

Or build, deploy and start hot reload in one step:

```bash
php artisan native:run android --watch
```

## Design system

The StyleHub look — near-black green canvas, lime accent, pill controls, rounded cards — is defined once as Tailwind theme tokens in `resources/css/app.css` (`bg-canvas`, `text-content`, `bg-accent`, `.card`, `.btn-accent`, `.chip`). Prefer those tokens over raw hex values.

Navigation chrome is native: the bottom tab bar is an EDGE `<native:bottom-nav>` in `resources/views/layouts/app.blade.php`, rendered outside the web view by the native shell. Its item URLs must stay **relative** — an absolute URL is treated as external and opens the device browser instead of navigating the app.

## Tests

```bash
php artisan test          # Pest
vendor/bin/pint --dirty   # formatting
vendor/bin/phpstan analyse
```
