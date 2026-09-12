<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Proof that the device authenticated the person holding it.
 *
 * A type whose only purpose is to be hard to obtain. `N4-R8` says a biometric
 * failure must not fall back to unlocked, and the way to make that structural is
 * to give {@see Lock::openedBy()} a parameter that a failure cannot produce.
 *
 * **It carries nothing**, and that is deliberate: there is nothing about a
 * successful authentication worth remembering. Which finger, which method,
 * whether it was biometric or the passcode — `N4-R8` treats passcode as a
 * legitimate outcome rather than a lesser one, so recording the difference
 * would invite a screen to make a distinction the requirement does not.
 *
 * The constructor is private and the only maker is {@see self::byTheDevice()},
 * which an adapter calls on a prompt that returned success. That is a thin
 * guarantee — an adapter could call it wrongly — and it is the right thin
 * guarantee: the mistake is now in one file that exists to be read carefully,
 * rather than available at every call site that has a boolean.
 */
final readonly class Authenticated
{
    private function __construct()
    {
        // Deliberately empty, and empty is the point.
        //
        // This type carries nothing. What it means is entirely in the fact that
        // one exists: the device asked, and the person holding it answered. A
        // field here — a timestamp, a method, a confidence — would be a second
        // thing to check, and the whole design is that there is nothing to
        // check because an `Authenticated` cannot be built any other way.
    }

    /**
     * The device said yes.
     *
     * Called only from an adapter holding a prompt result that succeeded.
     * Nothing else in this application should name it, and `A9` keeps it out of
     * a service provider.
     */
    public static function byTheDevice(): self
    {
        return new self();
    }
}
