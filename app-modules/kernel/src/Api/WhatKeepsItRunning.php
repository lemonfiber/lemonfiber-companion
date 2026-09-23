<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The service manager a machine has, or the absence of one this product configures.
 *
 * The words are the stack's own, from the `hosting` envelope. There is no
 * *unknown*: the contract's default is `Unsupported`, and it says why — a
 * machine nobody has told is a machine nothing is known about, and guessing at
 * a manager is how a report comes to claim a platform it never asked.
 *
 * **It is carried beside the commands rather than derived from them**, for the
 * reason {@see Daemons} gives about forms: a machine with no manager still has
 * long-running commands, every one of them `HowItIsHosted::Unsupported`, and a
 * screen reading the manager off the rows would have to infer *this platform*
 * from *every row on it* — which is the same answer until the day one row is
 * different, and then it is a platform claim made from a single service.
 */
enum WhatKeepsItRunning: string
{
    /** macOS, through a launch agent in the operator's own login session. */
    case Launchd = 'launchd';

    /** Linux, through a user service in the operator's own session. */
    case Systemd = 'systemd';

    /** A platform lemonfiber does not configure. */
    case Unsupported = 'unsupported';

    /**
     * Whether this machine has a manager the product sets up.
     *
     * The one question a screen asks before drawing anything about coming back
     * after a restart. False here is not *off*, and the sentence that follows it
     * is the contract's `instruction` — what to do instead — rather than a
     * control nobody can use.
     */
    public function configuresAnything(): bool
    {
        return $this !== self::Unsupported;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is how every word in this app reaches the
     * catalogue — see {@see Conclusion::saidOnTheScreen()} for the argument.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.keeps-running.%s', $this->value);
    }
}
