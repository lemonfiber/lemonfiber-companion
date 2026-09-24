<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where the services stand against the versions the stack's build pins, as the
 * stack answered it.
 *
 * The `update` envelope's top-level `state`. The stack works it out by
 * comparing what each service is running with the version pinned for it, and
 * says `updates-available` when at least one would move; this app holds no
 * opinion about which of two version strings is later, so it reads the word
 * rather than comparing anything itself.
 *
 * Five cases because the wire has five. A reading that nothing was applied in
 * answers only `current` or `updates-available`; the other three describe a
 * run that was agreed to, and are here so a stack sending one is read rather
 * than refused.
 *
 * **Not the changelog's `state`.** That one says whether the release record
 * matches the build that is running, and a record with no notes yet for this
 * build says `pending` there whether or not any service would move.
 */
enum AgainstThePins: string
{
    /** Every service is on the version pinned for it. */
    case Current = 'current';
    /** At least one service is pinned at a version it is not running. */
    case UpdatesAvailable = 'updates-available';
    /** Every service that had an update took it. */
    case Updated = 'updated';
    /** Some services took the update and one did not; the run stopped there. */
    case Partial = 'partial';
    /** No service took the update, and the run stopped where it did. */
    case Failed = 'failed';

    /**
     * What a screen says this is.
     *
     * The catalogue key is built from the case rather than listed against it,
     * so a case added without a line fails the rule that reads both.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('updates.pins.%s', $this->value);
    }

    /** Whether the stack said there is an update to take. */
    public function hasAnUpdateToTake(): bool
    {
        return $this === self::UpdatesAvailable;
    }
}
