<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about taking somebody out of the household.
 *
 * A closed set for {@see AskingThemIn}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at the call site is
 * this app able to ask for any action a stack offers. One act, asked twice:
 * unconfirmed it says what taking them out would cost, and confirmed it takes
 * them out.
 */
enum TakingThemOut: string
{
    /** Take somebody out of the household, or say what taking them out would cost. */
    case TakeThemOut = 'take_them_out';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: this app's word for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::TakeThemOut => 'remove',
        };
    }
}
