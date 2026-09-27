<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The four ways of moving in beside what is already on a machine, each an act of its own.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at a call site is
 * this app able to ask a stack for any action it offers. Each case's value is
 * the word a survey offers the mode under, so a mode the survey lists and
 * this set does not know is one no act is offered for.
 */
enum MovingInBy: string
{
    /** Take over the setup already here, as it stands. */
    case Adopting = 'adopt';

    /** Carry the old setup's own records across to lemonfiber's services. */
    case Importing = 'import';

    /** Stand lemonfiber up beside it, on ports of its own. */
    case StandingBeside = 'beside';

    /** Stop the setup already here and stand in its place. */
    case Replacing = 'replace';

    /**
     * lemonfiber's word for the act.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: the word a survey offers a mode under is not the action's name.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Adopting => 'migrate-adopt',
            self::Importing => 'migrate-import',
            self::StandingBeside => 'migrate-beside',
            self::Replacing => 'migrate-replace',
        };
    }
}
