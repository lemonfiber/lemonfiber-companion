<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether the platform wakes this app while it is away, so it can hear its stacks then.
 *
 * A port because only the platform can wake an app it closed: background
 * refresh on iOS, periodic work on Android. Behind it on a handset is
 * lemonfiber's own bridge; behind it in every test is a fake that records.
 *
 * **A wake carries nothing.** The platform says only that now is a moment the
 * app may run; what the app tells the operator is read over its own pinned
 * connection to each stack, never through Apple's or Google's push service.
 *
 * **When it runs is the platform's.** The platform decides whether and when,
 * and may wake it late or not at all, which is what the app says where
 * notifications are turned on.
 */
interface WakingWhileAway
{
    /**
     * Ask the platform to wake this app from time to time while it is away, and answer whether it took the request.
     *
     * Asking again changes nothing. A platform that would not take it is one
     * the operator turned background refresh off on, or one with nothing to
     * wake the app with.
     */
    public function ask(): bool;

    /** Stop asking, so no wake the platform still holds is delivered, and answer whether the platform let go of it. */
    public function stop(): bool;
}
