<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Handing one long-running command to a machine's service manager, or taking it back.
 *
 * Two acts, each asked for on its own and never arrived at: installing is not
 * a side effect of running a command or of any other act, so the only way a
 * machine comes to keep something running from here is an operator choosing
 * one of these.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at a call site is
 * this app able to ask a stack for any action it offers.
 */
enum HandingOver: string
{
    /** Hand it to the machine, which runs it for as long as it is installed. */
    case Install = 'install';

    /** Take it back, so it runs only while a terminal holds it. */
    case Remove = 'remove';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: this app's word for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Install => 'hosting-install',
            self::Remove => 'hosting-remove',
        };
    }

    /** The key for the question asked before it is sent, which takes the command's `:name`. */
    public function askedOnTheScreen(): string
    {
        return sprintf('stacks.handing_over_asked.%s', $this->value);
    }

    /** The key for what it will do to the machine, said beside that question. */
    public function meansOnTheScreen(): string
    {
        return sprintf('stacks.handing_over_means.%s', $this->value);
    }
}
