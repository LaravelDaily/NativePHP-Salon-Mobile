<?php

namespace App\NativeComponents\Layouts;

use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

/**
 * Chrome for screens pushed on top of the tab roots (reschedule, feedback,
 * stylists, settings). Replaces the v3 `<native:top-bar>` with
 * show-navigation-icon: a native navigation bar with a back chevron and the
 * screen's title, no tab bar.
 */
class SalonStackLayout extends NativeLayout
{
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function navBar(NativeComponent $screen): ?NavBar
    {
        return NavBar::make()
            ->back()
            ->title($screen->navTitle());
    }
}
