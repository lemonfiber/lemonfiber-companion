<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * What a look-up of a machine's name found, in the shim's word.
 *
 * Mirrors `ResolveRule`'s two answers on both platforms. Only {@see self::Found}
 * carries addresses; a word that is neither reads as nothing found.
 */
enum WhatTheLookupFound: string
{
    /** The name turned into at least one address the app can send to. */
    case Found = 'found';

    /** It turned into none: not found, not in time, or only addresses the app cannot use. */
    case Nothing = 'nothing';

    /**
     * What the shim answered under `outcome`, where it is one of these.
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
