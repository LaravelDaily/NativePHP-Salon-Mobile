<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover enables edge-to-edge rendering behind the notch. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="nativephp-safe-area bg-canvas font-sans text-content antialiased">
    {{ $slot }}

    {{--
        EDGE renders this as a real native tab bar outside the web view.
        The URLs must be relative: an absolute URL would be treated as external
        and opened in the device's browser instead of navigating the app.
    --}}
    <native:bottom-nav label-visibility="selected">
        <native:bottom-nav-item
            id="home"
            icon="home"
            label="Home"
            :url="route('home', absolute: false)"
            :active="request()->routeIs('home')"
        />
        <native:bottom-nav-item
            id="appointments"
            icon="calendar"
            label="Appointments"
            :url="route('appointments', absolute: false)"
            :active="request()->routeIs('appointments')"
        />
        <native:bottom-nav-item
            id="book"
            icon="add"
            label="Book"
            :url="route('book', absolute: false)"
            :active="request()->routeIs('book')"
        />
        <native:bottom-nav-item
            id="profile"
            icon="person"
            label="Profile"
            :url="route('profile', absolute: false)"
            :active="request()->routeIs('profile')"
        />
    </native:bottom-nav>
</body>
</html>
