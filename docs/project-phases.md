# StyleHub — Salon Booking Platform: Implementation Phases

A NativePHP for Laravel demo project based on a real Upwork brief (see `docs/project-description.md` and mockups in `docs/mockups/`). Two codebases:

| Repo | Path | Role |
|---|---|---|
| **salon-mobile** | `/Users/povilas/Herd/salon-mobile` | Customer mobile app — NativePHP Mobile v3, Livewire 4, Tailwind 4, EDGE components |
| **salon-api** | `/Users/povilas/Herd/salon-api` | Backend — Laravel API (`/api/v1`, Sanctum) + Filament admin panel for the salon owner |

Each phase below is a self-contained deliverable intended to be delegated to an implementing agent, one phase per session. Every phase states which repo(s) it touches, its scope, and acceptance criteria.

---

## Confirmed Decisions (from planning interview, 2026-07-10)

- **Auth:** SMS OTP is the primary flow (per brief) **plus** email/password fallback (per mockups). OTP delivery goes through a driver-based SMS sender — `log` driver in local dev (code visible in logs / returned in debug mode), swappable for Twilio/Vonage later. No Google/social login.
- **Tenancy:** Single salon. The app is branded for one salon ("StyleHub"); no salon selection screens. Keep the API schema clean but do not build tenant infrastructure.
- **API scope:** Full API **and** a Filament admin panel with core management: services, specialists (staff), staff schedules, and appointments (list/calendar + status management). No dashboard widgets, no offer broadcasting.
- **Extra features in scope:** Favorites / "My Stylists" and salon feedback/ratings. **Out of scope:** payments, offers/promotions, Google sign-in.
- **Notifications:** Real push via NativePHP `mobile-firebase` plugin (FCM), triggered by the API for confirmations/cancellations/reschedules, plus a scheduled API command for appointment reminders. Also an in-app Notifications screen.
- **Availability engine:** Real computed slots — staff working-hour schedules, per-service durations, slots computed as (schedule − existing appointments), with server-side overlap prevention.
- **Platform:** Android-first development (emulator); iOS verified later.
- **Mobile UI:** EDGE native components (bottom-nav, top-bar) for navigation chrome; Livewire + Tailwind screens styled closely to the dark-green mockups.
- **Testing:** Full coverage on both sides. Pest feature tests for every API endpoint, unit tests for the availability engine, Livewire component tests for mobile screens, Pest 4 browser tests where feasible.

## Cross-Cutting Rules for the Implementing Agent

1. **Never run NativePHP build/run commands** (`native:install`, `native:run`, `native:watch`, `npm run build -- --mode=...`). Always instruct the user to run them and wait for feedback. Platform is **Android** unless the user says otherwise.
2. **Activate the `nativephp-mobile` skill** whenever touching NativePHP functionality, and fetch the relevant nativephp.com v3 docs before implementing a native feature.
3. **API conventions:** versioned routes under `/api/v1`, Form Requests for validation, Eloquent API Resources for responses, policies for authorization, rate limiting on auth endpoints. Enums for statuses (TitleCase keys).
4. **Mobile is API-first:** the on-device SQLite database stores only local state (auth token lives in SecureStorage; no mirroring of salon data). All salon data comes from the API via an HTTP client service.
5. **Dev connectivity:** the API is served by Herd at `https://salon-api.test`. The Android emulator cannot resolve `.test` domains — solve this in Phase 2 (e.g. base URL config pointing at `http://10.0.2.2` with a `Host: salon-api.test` header, `herd share`, or similar) and document the choice in the mobile README.
6. **Paid plugins heads-up:** `nativephp/mobile-secure-storage` ($49) and `nativephp/mobile-firebase` (proprietary) require licenses. Confirm with the user that licenses are available before the phase that needs them; if not, agree on a fallback with the user before proceeding.
7. **Quality gates per phase:** `vendor/bin/pint --dirty --format agent`, Larastan passes, `php artisan test --compact` green in the touched repo(s), before the phase is considered done.
8. **Design fidelity:** match the mockups' dark theme — near-black green background, lime/chartreuse accent (`#C6F432`-ish), pill-shaped buttons and chips, rounded cards. Define these once as Tailwind theme tokens in Phase 2 and reuse everywhere.

---

## Phase 1 — API Domain Foundation

**Repo:** salon-api

Set up the API skeleton and the whole salon domain model so every later phase has data to work with.

**Scope:**
- Install/configure Sanctum, Pest, Larastan, Pint (match salon-mobile's tooling). Base `/api/v1` route structure.
- Migrations, models, factories, seeders for: `User` (customer; phone, phone_verified_at, email, password nullable, notification preferences), `Specialist` (name, photo, bio, active), `Service` (name, icon, duration_minutes, price, active), `service_specialist` pivot (which specialist performs which service), `SpecialistSchedule` (specialist, weekday, start/end time), `Appointment` (user, specialist, service, starts_at, ends_at, status enum: Pending/Confirmed/Completed/Cancelled).
- Rich demo seed data mirroring the mockups: ~5 specialists (Clay Burke, Eileen Rose, …), ~6 services (Men's Precision Cut, Beard Grooming, Scalp Treatment, Hair Color, …), realistic weekly schedules, a spread of past/upcoming appointments for a demo user.
- A read-only `GET /api/v1/salon` endpoint returning salon info (name, address, opening summary) for the app's use.

**Acceptance criteria:**
- `php artisan migrate:fresh --seed` produces a fully populated demo salon.
- Factories cover all models with sensible states (e.g. `Appointment::factory()->completed()`, `->upcoming()`).
- Model relationships and enums are unit-tested; the salon endpoint has a feature test.

---

## Phase 2 — Mobile App Shell

**Repo:** salon-mobile

Turn the fresh Laravel app into a running NativePHP Android app with navigation chrome and the design system.

**Scope:**
- Guide the user through NativePHP Mobile v3 install: `composer require nativephp/mobile`, `.env` vars (`NATIVEPHP_APP_ID`, `NATIVEPHP_APP_VERSION=DEBUG`, `NATIVEPHP_APP_VERSION_CODE`), `native:install`, Vite config with `nativephpMobile()` + `nativephpHotFile()`. (User runs the commands.)
- Tailwind v4 theme tokens for the StyleHub design language (colors, radii, typography) in `resources/css/app.css`.
- Base Blade layout: viewport meta with safe-area handling (`nativephp-safe-area`), EDGE `<native:bottom-nav>` with 4 items — Home, Appointments, Book (new appointment), Profile — and `<native:top-bar>` where screens need it.
- Placeholder Livewire screens for each tab, wired to named routes, dark theme applied.
- API client foundation: a configured HTTP client service (`ApiClient` or similar) with base URL from config, JSON handling, and error translation. Solve and document Android-emulator→Herd connectivity (see cross-cutting rule 5).
- App icon + splash screens (`public/icon.png`, `public/splash.png`) with placeholder StyleHub branding.

**Acceptance criteria:**
- User confirms the app boots on the Android emulator with working bottom-nav navigation between the four placeholder screens, in the dark StyleHub theme.
- The app can successfully fetch `GET /api/v1/salon` from the Herd-served API and render the salon name on the Home screen.
- Livewire component tests exist for the placeholder screens (render + basic assertions).

---

## Phase 3 — Authentication (SMS OTP + Email/Password)

**Repos:** salon-api + salon-mobile

**Scope — API:**
- OTP flow: `POST /v1/auth/otp/request` (phone → creates/limits an OTP, sends via driver-based SMS sender; `log` driver in dev) and `POST /v1/auth/otp/verify` (phone + code → registers-or-logs-in, returns Sanctum token). OTP: 6 digits, short expiry, limited attempts, rate-limited per phone and IP.
- Email/password flow: `POST /v1/auth/register`, `POST /v1/auth/login` returning Sanctum tokens; `POST /v1/auth/logout`; `GET /v1/me`.
- SMS sender contract (`SmsSender` interface) with `LogSmsSender` implementation; config-driven so Twilio/Vonage can be added without touching call sites.

**Scope — Mobile:**
- Welcome/sign-in screen (matches "Welcome Back" mockup), Create Account screen (matches mockup: name, email, phone, password), and OTP entry screen (phone input → code input with resend countdown).
- Token stored via `SecureStorage` (requires the paid plugin — confirm license, see rule 6); an auth middleware/guard that redirects unauthenticated users to the welcome screen; logout clears the token.
- Authenticated state: `/v1/me` fetched after login, user info available to all screens.

**Acceptance criteria:**
- Full Pest feature coverage of both auth flows including rate limiting, expired/wrong OTP, duplicate phone/email.
- Livewire tests for the three mobile auth screens (validation errors, happy path with mocked API).
- User verifies on the emulator: register with phone → grab OTP from API log → land on Home; kill app → still logged in; logout → back to welcome.

---

## Phase 4 — Availability Engine + Catalog API

**Repo:** salon-api

The heart of the booking engine — built and tested before any booking UI exists.

**Scope:**
- `GET /v1/services` and `GET /v1/specialists` (with services each specialist performs, photo URLs).
- `AvailabilityService` (or similarly named domain service): given service + specialist + date, compute bookable start times from the specialist's weekly schedule, stepped in 30-minute increments, subtracting slots that would overlap existing non-cancelled appointments (using the service's duration), and excluding past times for today.
- `GET /v1/availability?service_id&specialist_id&date` returning slots grouped for the UI (Morning/Afternoon/Evening).
- Also support querying availability across all specialists for a service (`specialist_id` optional → per-specialist slot map), so the UI can show which specialists are free.

**Acceptance criteria:**
- The availability engine has exhaustive unit tests: edge-of-schedule slots, overlapping appointments of different durations, back-to-back bookings, cancelled appointments freeing slots, day with no schedule, today's past-time exclusion.
- Endpoint feature tests cover filtering and validation.
- This phase is API-only; no mobile changes.

---

## Phase 5 — Booking Flow (Mobile)

**Repos:** salon-api + salon-mobile

**Scope — API:**
- `POST /v1/appointments` — validates the requested slot against `AvailabilityService` at write time (race-safe: re-check inside a transaction/lock), creates a Confirmed appointment, returns it as a resource. Conflict → 422 with a clear message.

**Scope — Mobile:**
- New Appointment screen matching the mockups: month header, horizontal date strip, time-slot chips grouped by Morning/Afternoon/Evening, service selection list with icons, specialist picker (avatar row with checkmark, as in mockup 2), summary card (Service | Date | Time | Specialist), and Confirm Booking button.
- Livewire-driven reactivity: changing date/service/specialist re-fetches slots from the API; unavailable combinations disable the confirm button.
- Success state (confirmation with haptic/toast via `Device`/`Dialog` plugins) and friendly handling of the slot-taken conflict (re-fetch slots, prompt to pick again).

**Acceptance criteria:**
- API: feature tests for creation, double-booking rejection, and validation.
- Mobile: Livewire tests for the booking component's state transitions and API interaction (mocked).
- User verifies end-to-end on the emulator: book a real appointment, see it appear in the Filament-less DB (via seeder user), and confirm a second booking of the same slot is rejected gracefully.

---

## Phase 6 — Appointment Management

**Repos:** salon-api + salon-mobile

**Scope — API:**
- `GET /v1/appointments` (filter: upcoming/past, paginated), `GET /v1/appointments/{id}`.
- `PATCH /v1/appointments/{id}` for reschedule (new slot, re-validated through the availability engine) and `DELETE` (or status transition) for cancel. Policy: users manage only their own appointments; completed/cancelled ones can't be modified; optional cutoff (e.g. can't cancel < 2h before start — make it a config value).

**Scope — Mobile:**
- Appointments tab matching the mockup: "Upcoming Appointments" cards (service, specialist avatar + name, date/time, Reschedule + Cancel buttons) and a Past/History section (the "Booking History — 3 Completed" concept from the Profile mockup).
- Reschedule flow reuses the Phase 5 slot picker prefilled with the existing appointment; cancel shows a native confirm dialog (`Dialog` plugin).
- Home screen becomes a real dashboard: next upcoming appointment card + quick "Book now" entry.

**Acceptance criteria:**
- Feature tests: listing/filtering, reschedule happy path + conflicts + policy denials, cancel rules incl. cutoff.
- Livewire tests for list rendering, cancel and reschedule flows.
- User verifies on emulator: reschedule an appointment to a new slot, cancel another, see history update.

---

## Phase 7 — Filament Admin Panel

**Repo:** salon-api

**Scope:**
- Filament panel (latest version compatible with Laravel 13) at `/admin` with its own admin auth (seeded owner account).
- Resources: Services (CRUD, active toggle), Specialists (CRUD, photo upload, services relation), Specialist Schedules (manageable per specialist — relation manager with weekday/time rows), Appointments (table with filters by date/status/specialist, status transitions Confirm/Complete/Cancel, and a calendar-style or grouped-by-day view — simple is fine).
- Customers list (read-only) as a supporting resource.
- Admin-side status changes must fire the same domain events later used for push (Phase 8) — introduce events like `AppointmentCancelled`, `AppointmentRescheduled` now, even if the only listener so far writes an in-app notification record.

**Acceptance criteria:**
- Feature tests for each resource (Filament testing helpers): list, create, edit, status transitions with their guard rules.
- The owner can run the whole demo from `/admin`: adjust a schedule and see mobile availability change accordingly (user verifies manually).

---

## Phase 8 — Notifications: Push + Reminders + In-App

**Repos:** salon-api + salon-mobile

**Scope — Mobile:**
- Install `nativephp/mobile-firebase` (proprietary — confirm license, rule 6); user creates the Firebase project and places `google-services.json` in the app root (agent guides, user executes).
- On login/startup: `PushNotifications::getToken()` + `#[OnNative(TokenGenerated::class)]` handler that posts the token to the API (`POST /v1/device-tokens`).
- Notifications screen matching the mockup: New Booking Confirmed / Appointment Reminder / Appointment Rescheduled entries with icons and relative context, fed from `GET /v1/notifications`.

**Scope — API:**
- `device_tokens` table (user, token, platform); FCM HTTP v1 sender using `fcm-service-account.json` (config-driven, faked in tests).
- Laravel notifications (database + FCM channels) for: booking confirmed, cancelled (by user or admin), rescheduled — wired to the Phase 7 domain events.
- `salon:send-appointment-reminders` scheduled command: pushes a reminder N hours before each upcoming appointment (N configurable, default 2), idempotent (never double-sends).
- `GET /v1/notifications` + mark-as-read endpoint.

**Acceptance criteria:**
- Tests: token registration, each notification's content and recipients (FCM channel faked), reminder command idempotency and timing window, notifications feed.
- User verifies on emulator/device: real push arrives on booking and on admin-side cancellation from Filament; Notifications screen lists history.

---

## Phase 9 — Favorites & Feedback

**Repos:** salon-api + salon-mobile

**Scope — API:**
- Favorites: `POST/DELETE /v1/specialists/{id}/favorite`, favorites included in `/v1/specialists` and a `GET /v1/me/stylists` list ("My Stylists").
- Feedback: `POST /v1/appointments/{id}/feedback` (rating 1–5 + optional comment; only own, Completed, not-yet-reviewed appointments), average rating exposed on salon/specialist resources. Feedback visible read-only in Filament (extend the Appointments resource or a small Feedback resource).

**Scope — Mobile:**
- "My Stylists" row on the Profile screen (avatar stack, as in mockup) linking to a favorites list; heart/toggle on specialist cards; favorites sorted first in the booking flow's specialist picker.
- "Salon Feedback" entry on Profile (star display, per mockup); prompt to rate after an appointment is Completed (banner on Home or on the History list); rating form with stars + comment.

**Acceptance criteria:**
- Feature tests: favorite toggling, feedback rules (ownership, status, one-per-appointment), rating aggregation.
- Livewire tests for the rating form and favorites toggling.
- User verifies: favorite a stylist → they lead the booking picker; complete an appointment in Filament → app prompts for a rating.

---

## Phase 10 — Profile, Settings & Final Polish

**Repos:** salon-mobile (+ small API additions)

**Scope:**
- Profile screen matching the mockup: avatar, name, email, Edit Profile (name, email, phone — phone change re-verifies via OTP), and the menu rows built in earlier phases (My Stylists, Booking History, Salon Feedback, App Settings).
- Settings screen: notification toggles (per-type preferences persisted via API and respected by Phase 8 senders), logout, and account deletion (confirm dialog → `DELETE /v1/me` → cascade/anonymize server-side, wipe local token).
- Polish pass across all screens: loading skeletons, empty states (no appointments, no notifications), offline handling via `Network::status()` with a friendly banner, haptic feedback on key actions, safe-area/scroll checks.
- Final test sweep: full Pest suites green in both repos, Larastan clean, Pest 4 browser tests for the critical mobile flows where feasible (auth, booking).
- README updates in both repos: setup, Firebase/plugin requirements, demo walkthrough script. iOS build verification handed to the user as a follow-up checklist (`native:run ios`).

**Acceptance criteria:**
- Notification preferences actually suppress pushes (tested).
- Account deletion removes tokens/appointments correctly (tested) and returns the app to the welcome screen (user-verified).
- Both test suites green; the user can run the complete demo end-to-end on Android following the README script.
