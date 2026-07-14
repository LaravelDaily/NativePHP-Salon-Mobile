<?php

use Nativephp\NativeUi\Theme;

it('loads the StyleHub palette into the native UI theme', function () {
    expect(Theme::get('light.primary'))->toBe('#C6F432')
        ->and(Theme::get('light.on-primary'))->toBe('#0B1005')
        ->and(Theme::get('light.background'))->toBe('#080D07')
        ->and(Theme::get('dark.background'))->toBe('#080D07')
        ->and(Theme::get('light.destructive'))->toBe('#FF6B5E');
});

it('keeps the dark palette identical to light, as the app is dark by design', function () {
    foreach (['primary', 'surface', 'background', 'outline', 'destructive', 'accent'] as $token) {
        expect(Theme::get("dark.$token"))->toBe(Theme::get("light.$token"));
    }
});
