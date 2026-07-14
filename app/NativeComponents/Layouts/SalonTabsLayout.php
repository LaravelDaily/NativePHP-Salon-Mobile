<?php

namespace App\NativeComponents\Layouts;

use App\Icons\Android;
use App\Icons\Ios;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

/**
 * Chrome for the four signed-in root screens. Replaces the v3
 * `<native:bottom-nav>` that the web view layout injected: in v4 the tab
 * bar belongs to the layout, and every screen wrapped in it shares one
 * native bar whose state survives navigation.
 */
class SalonTabsLayout extends NativeLayout
{
    /**
     * Render the chrome through the platform's own containers (SwiftUI
     * NavigationStack/TabView, Liquid Glass on iOS 26+) instead of
     * EDGE-drawn bars — the whole point of moving to v4.
     */
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function navBar(NativeComponent $screen): ?NavBar
    {
        if (! method_exists($screen, 'navTitle')) {
            return null;
        }

        return NavBar::make()->title($screen->navTitle());
    }

    public function tabBar(NativeComponent $screen): ?TabBar
    {
        return TabBar::make()
            ->activeColor('#C6F432')
            ->add(Tab::link('Home', '/', ios: Ios::House, android: Android::Home))
            ->add(Tab::link('Appointments', '/appointments', ios: Ios::Calendar, android: Android::CalendarMonth))
            ->add(Tab::link('Book', '/book', ios: Ios::Plus, android: Android::Add))
            ->add(Tab::link('Profile', '/profile', ios: Ios::Person, android: Android::Person));
    }
}
