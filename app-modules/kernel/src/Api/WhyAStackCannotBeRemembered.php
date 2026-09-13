<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why this device could not write down a stack it was introduced to.
 *
 * Its own enum rather than {@see WhySessionCannotBeKept}, which names the same
 * two conditions about the same store. They are told apart because what an
 * operator should do about them is different: a session that cannot be kept is
 * a sign-in that will not survive the app closing, and a stack that cannot be
 * remembered is a pairing that did not happen at all — the operator scanned a
 * code and has nothing to show for it. One catch in front of both would report
 * the milder of the two.
 */
enum WhyAStackCannotBeRemembered: string
{
    /** The device offers no store this app may write to. */
    case DeviceHasNoSecureStorage = 'no_secure_storage';

    /** There is a store and it would not open — locked, or full, or refusing. */
    case StoreWouldNotOpen = 'store_would_not_open';
}
