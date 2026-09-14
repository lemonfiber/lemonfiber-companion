<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What became of one service when an update was applied.
 *
 * Four cases and not a boolean, which is the whole of `N2-R18`. *Not fetched*,
 * *not started* and *not reached* are a network, a service and a machine — and
 * flattened into *failed* they send an operator to look in the wrong place,
 * which on a phone at eleven at night is the difference between a fix and a
 * morning.
 */
enum HowItEnded: string
{
    /** It took the new version and came back. */
    case Updated = 'updated';
    /** The new image never arrived, so nothing was tried. */
    case NotFetched = 'not-fetched';
    /** The image arrived and the service would not come back up on it. */
    case NotStarted = 'not-started';
    /** It started and never answered, so what it is doing is unknown. */
    case NotReached = 'not-reached';

    /**
     * What a screen says this is.
     *
     * Built from the case rather than listed against it, so a case added
     * without a line fails the rule that reads both.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('updates.ended.%s', $this->value);
    }

    /**
     * Whether this service is where the operator wanted it.
     *
     * The one question a summary asks, answered once. Three ways of not
     * arriving stay three on the row that names the service, and become one
     * only where the screen is counting.
     */
    public function arrived(): bool
    {
        return $this === self::Updated;
    }

    /**
     * Whether what this service is doing now is unknown.
     *
     * Apart from the other two failures because it is the one where the stack
     * is not telling the operator something it knows — it is telling them it
     * does not know. A screen that offered a remedy for this would be guessing
     * at which of several situations it is.
     */
    public function leftUnanswered(): bool
    {
        return $this === self::NotReached;
    }
}
