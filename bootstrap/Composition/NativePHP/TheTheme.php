<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Modules\Design\Api\Theme;
use Native\Mobile\Edge\TailwindParser;

/**
 * Whose palette `bg-theme-*` and `text-theme-*` resolve against.
 *
 * EDGE ships no theme resolver, so without one every `bg-theme-*` class is
 * parsed, found to mean nothing, and dropped — silently, at render, on
 * somebody's phone. The design module owns what those tokens mean; this is what
 * connects the two.
 *
 * **It is a class rather than four lines in `boot()` because the ordering is
 * the thing that has to be provable.** `TailwindParser` holds one resolver and
 * keeps whichever was set last, and `nativephp/mobile-ui` sets one of its own —
 * a light one *and* a dark one — in its own boot. Which palette the application
 * actually paints with was therefore decided by provider discovery order, which
 * is not something either package chooses and which differs between a fresh
 * `composer install` and an incremental one.
 *
 * That is not a hypothetical: the assertion in `TheThemeIsRegisteredAtBootTest`
 * passed locally and failed in CI on the same commit, which is exactly what a
 * race between two providers looks like from the outside. A test that only asks
 * "what is registered now" cannot tell a fix from luck — so the registration is
 * a method something can call, and the test plants a foreign resolver and calls
 * it.
 *
 * The composition root calls it from `$this->app->booted()`, which is after
 * every provider's boot rather than after its own. Same remedy, same reason, as
 * {@see ScreenRoutes}.
 */
final readonly class TheTheme
{
    /**
     * Make this application's palette the one the parser resolves against.
     *
     * Safe to call more than once, and called once. Setting a resolver is a
     * replacement rather than an addition, which is the property the ordering
     * problem turns on and the property that makes this idempotent.
     */
    public static function paint(): void
    {
        TailwindParser::setThemeResolver(Theme::resolver());

        // Cleared rather than left alone, because "registered no dark resolver"
        // and "somebody else registered one" are the same state to the parser.
        // Without this the plugin's dark palette rides along under this
        // application's light one: every `bg-theme-*` gains a dark companion
        // nothing here chose, and `DES-R24`'s measured pair stops being what is
        // drawn. `ThemeToken::hex()` carries why there is no dark companion —
        // ink on lemon measures 10.9:1 whichever way a reader has their phone
        // set, so there is nothing for a second palette to improve.
        TailwindParser::setThemeDarkResolver(null);
    }
}
