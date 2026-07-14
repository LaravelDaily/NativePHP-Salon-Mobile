<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Nativephp\NativeUi\Theme::merge([...])
 * from a service provider. Runtime merges deep-merge on top of these values.
 *
 * Decision log: /docs/NATIVE-UI-REWRITE-PLAN.md (D — theme layer)
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Theme
    |---------------------------------------------------------------------------
    |
    | 17 color tokens, 4 radii, 4 font sizes, font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | Color tokens accept:
    |   - CSS hex: '#B91C1C', '#F00', or with alpha '#8B5CF680' (#RRGGBBAA)
    |   - Tailwind palette names: 'red-300', 'orange-800'
    |   - Opacity modifiers on either: 'red-300/20', '#8B5CF6/50'
    |
    | Dark mode is auto-derived from `light` when `dark` is not set. To opt
    | into explicit dark tokens, fill out the `dark` block.
    |
    | The default pairs meet WCAG AA (4.5:1) — if you customize, keep each
    | `on-*` color at 4.5:1 contrast against its background token.
    |
    */

    'theme' => [

        /*
        | StyleHub design language — dark, near-black green canvas with a lime
        | accent (see resources/css/app.css and docs/mockups). The app is dark
        | by design, so light and dark share the same palette.
        */
        'light' => [
            // Lime accent with near-black ink on top — filled buttons, active states.
            'primary' => '#C6F432',
            'on-primary' => '#0B1005',

            // Muted action color — chips and secondary controls (surface-raised).
            'secondary' => '#1A2617',
            'on-secondary' => '#97A58D',

            // Surface = cards, sheets, dialogs. Background = page root canvas.
            'surface' => '#111A0F',
            'on-surface' => '#F2F5EF',
            'background' => '#080D07',
            'on-background' => '#F2F5EF',

            // Surface variant = filled text fields, muted tonal surfaces.
            // on-surface-variant = muted label/hint text on those surfaces.
            'surface-variant' => '#1A2617',
            'on-surface-variant' => '#97A58D',

            // Outline = hairline borders (text fields, dividers, cards).
            'outline' => '#26331F',

            // Destructive actions — maps to `variant="destructive"` on components.
            'destructive' => '#FF6B5E',
            'on-destructive' => '#0B1005',

            // Tertiary accent — the warning gold used for ratings and highlights.
            'accent' => '#F5C451',
            'on-accent' => '#0B1005',
        ],

        'dark' => [
            'primary' => '#C6F432',
            'on-primary' => '#0B1005',

            'secondary' => '#1A2617',
            'on-secondary' => '#97A58D',

            'surface' => '#111A0F',
            'on-surface' => '#F2F5EF',
            'background' => '#080D07',
            'on-background' => '#F2F5EF',

            'surface-variant' => '#1A2617',
            'on-surface-variant' => '#97A58D',

            'outline' => '#26331F',

            'destructive' => '#FF6B5E',
            'on-destructive' => '#0B1005',

            'accent' => '#F5C451',
            'on-accent' => '#0B1005',
        ],

        // Corner radii (points / dp). Cards in the mockups are generously
        // rounded (1.5rem ≈ 24dp); controls are full pills.
        'radius-sm' => 8,
        'radius-md' => 16,
        'radius-lg' => 24,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,

        // 'System' resolves to the platform default (San Francisco on iOS, Roboto on Android).
        // Set a bundled font token to apply it app-wide — a file from your app's
        // resources/fonts/ minus the extension (e.g. 'Inter-Regular'). Download one
        // with `php artisan native:font Inter`. Per-element `font` attributes and
        // font-serif / font-mono classes still win over this default.
        'font-family' => 'System',
    ],

];
