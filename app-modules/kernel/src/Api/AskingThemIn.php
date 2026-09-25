<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about letting somebody in.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at the call site is
 * this app able to ask for any action a stack offers. These two are the whole
 * of what the invitation screen can ask for.
 */
enum AskingThemIn: string
{
    /** Offer somebody an account, or say what offering one would come to. */
    case Invite = 'invite';

    /** Take the password off an account that is already theirs, so they choose the next one. */
    case TakeThePasswordOff = 'take_the_password_off';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: this app's word for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Invite => 'invite',
            self::TakeThePasswordOff => 'reissue',
        };
    }
}
