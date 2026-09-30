<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * The words the store answers under `outcome` where it did as it was asked.
 *
 * Both native halves write these, and {@see Storage} reads them here and
 * nowhere else. A refusal, a word neither half writes and no answer at all are
 * none of them, and each call reads that as the least it could mean.
 */
enum HowTheStoreAnswered: string
{
    /** A value was written. */
    case Kept = 'kept';

    /** A value was read. */
    case Found = 'found';

    /** There is no value under that key. */
    case Nothing = 'nothing';

    /** A value was removed. */
    case Forgotten = 'forgotten';

    /**
     * What the store answered under `outcome`, where it is one of these.
     *
     * Two branches rather than `tryFrom($said ?? '')`, for the reason
     * {@see WhyNothingWasKept::orTheStoreWouldNotOpen()} gives.
     */
    public static function in(WhatTheBridgeAnswered $said): ?self
    {
        $word = $said->outcome();

        return $word === null ? null : self::tryFrom($word);
    }
}
