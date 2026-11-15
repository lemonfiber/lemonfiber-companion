<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/** The word a waking function answers with, as both native halves write it. */
enum HowWakingAnswered: string
{
    /** The distributors installed are listed. */
    case Listed = 'listed';

    /** This phone cannot be woken through a distributor. */
    case NotHere = 'not_here';

    /** An endpoint was given. */
    case Given = 'given';

    /** No endpoint was given. */
    case NotGiven = 'not_given';

    /** A registration and its key pair were let go of. */
    case LetGo = 'let_go';

    /** The case the answer's outcome names, or none. */
    public static function in(WhatTheBridgeAnswered $said): ?self
    {
        $word = $said->outcome();

        return $word === null ? null : self::tryFrom($word);
    }
}
