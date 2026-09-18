<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the platform will let this application do about being photographed.
 *
 * A port because a window is not a thing a PHP process has. Behind it on a
 * handset is lemonfiber's own native expansion — a window flag on Android, a
 * cover view on iOS; behind it everywhere else is a fake, which is what lets a
 * test about `N4-R18` be written at all.
 *
 * **The app is protected while backgrounded no matter what.** `N4-R9` is
 * enforced by the native half on its own, from a lifecycle observer installed at
 * launch, and there is deliberately no method here for it: a requirement with no
 * exceptions should not have an off switch, and an interface offering one is an
 * invitation to find a screen that seems to want it.
 *
 * **What this port is for is `N4-R18`** — the screens that must also be
 * protected while somebody is looking at them, because a screen recording runs
 * in the foreground. Which screens those are is declared by {@see Concealed},
 * and this is how that declaration reaches the window.
 *
 * Every method answers with whether the window is protected *now* rather than
 * with whether the call succeeded. Those differ, and the difference is the
 * honest part: revealing on a backgrounded app leaves it protected, and on a
 * machine with no window at all nothing is ever protected.
 */
interface Capture
{
    /**
     * Protect the window while a guarded screen is up.
     *
     * Answers whether the window ended up protected. On iOS that is true for a
     * screen recording and for the task switcher and false for a deliberate
     * screenshot, which the platform does not let anybody block — the adapter
     * says so where somebody will read it rather than leaving the difference to
     * be discovered.
     */
    public function conceal(): bool;

    /**
     * Stop protecting for that screen.
     *
     * Answers whether the window is still protected, because it may well be:
     * `N4-R9` protects a backgrounded app whatever it is showing, so revealing
     * while away changes nothing an operator could see.
     */
    public function reveal(): bool;

    /** Whether the window is protected from capture right now. */
    public function isProtected(): bool;
}
