<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about taking lemonfiber off its machine.
 *
 * A closed set for {@see AskingThemIn}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at the call site is
 * this app able to ask for any action a stack offers. One act: taking one of
 * the four removals off the machine, against the reading of it the operator
 * was shown.
 */
enum TakingItOff: string
{
    /** Take one of the four removals off the machine. */
    case TakeItOff = 'take_it_off';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: this app's word for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::TakeItOff => 'uninstall',
        };
    }
}
