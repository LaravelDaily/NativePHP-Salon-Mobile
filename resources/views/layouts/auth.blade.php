<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- No bottom-nav here: there is nowhere to navigate to until you are signed in. --}}
<body class="nativephp-safe-area bg-canvas font-sans text-content antialiased">
    {{ $slot }}
</body>
</html>
