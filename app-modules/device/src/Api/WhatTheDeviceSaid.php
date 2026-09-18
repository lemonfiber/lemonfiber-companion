<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Kernel\Api\Asked;

/**
 * The platform's own word for what somebody said about a permission.
 *
 * Five words, and they are not this application's: they are what
 * `PushNotifications::checkPermission()` answers, which is what iOS and Android
 * report through `nativephp/mobile`. {@see Asked} is the concept this codebase
 * reasons in — three cases, chosen for the questions a screen asks — and the
 * two are deliberately separate types. Collapsing them would mean either
 * carrying the platform's five everywhere or deciding, at every call site, that
 * `provisional` is near enough to granted.
 *
 * **A type rather than five literals in a `match`.** That is the same argument
 * {@see \Modules\Kernel\Api\Scheme} makes about `'https'`, and it arrived the
 * same way: the literals were written, they were correct, and nothing could see
 * them. A misspelling of `'not_determined'` is a silent fall to the default arm
 * — which reports the operator as never asked, and re-opens the prompt they
 * already answered.
 *
 * `tryFrom()` rather than `from()` at the boundary: an answer this enum does
 * not know is a platform that grew a sixth word, and that is a thing to read as
 * "unknown" rather than to raise on. {@see self::orNothingSaid()} is where that
 * decision lives, so it is made once.
 */
enum WhatTheDeviceSaid: string
{
    /** The operator allowed it. */
    case Granted = 'granted';

    /** The operator refused it, and nothing may ask again. */
    case Denied = 'denied';

    /** Nobody has been asked, so the point of first use is still ahead. */
    case NotDetermined = 'not_determined';

    /**
     * iOS's quiet delivery — allowed without the operator being asked first.
     *
     * A grant for the only question this application asks of it: whether a
     * notification may be shown. It may, quietly.
     */
    case Provisional = 'provisional';

    /** An App Clip's temporary grant. Also a grant, for the same reason. */
    case Ephemeral = 'ephemeral';

    /**
     * What the platform said, or that it said nothing this type recognises.
     *
     * `null` arrives from a bridge with no device behind it, which is every
     * machine that is not a handset. An unrecognised word arrives from a
     * platform that grew a case since this was written. Both are read as
     * {@see self::NotDetermined}, which is the safe answer in both directions:
     * it withholds the notification, and it leaves asking possible rather than
     * recording a refusal nobody made.
     */
    public static function orNothingSaid(?string $said): self
    {
        return self::tryFrom($said ?? '') ?? self::NotDetermined;
    }

    /**
     * What this means in the terms the rest of the application reasons in.
     *
     * Five words become three cases here rather than at each call site, which
     * is the point of having both types: `provisional` and `ephemeral` are
     * grants for the question this app asks — may a notification be shown —
     * and somewhere that has to be written down once.
     */
    public function means(): Asked
    {
        return match ($this) {
            self::Granted, self::Provisional, self::Ephemeral => Asked::Granted,
            self::Denied => Asked::Declined,
            self::NotDetermined => Asked::NotYet,
        };
    }
}
