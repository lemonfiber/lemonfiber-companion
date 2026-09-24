<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether this machine's settings let one of lemonfiber's requests go out.
 *
 * Two cases rather than a boolean, so a row says which it is in words at every
 * call site rather than as a `true` somebody has to look up. *Switched off* is
 * not *nothing configured to reach*: a request can be allowed and have nowhere
 * to go, and the two are separate facts on the row.
 */
enum WhetherItIsAllowed: string
{
    /** The settings let it go out. */
    case Allowed = 'allowed';

    /** The setting that switches it off is off. */
    case SwitchedOff = 'switched_off';

    /** Read off the wire's boolean, which is the only place one is taken. */
    public static function said(bool $allowed): self
    {
        return $allowed ? self::Allowed : self::SwitchedOff;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.outbound.allowed.%s', $this->value);
    }
}
