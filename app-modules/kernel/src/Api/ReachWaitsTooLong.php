<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A bound was asked for that is not a bound an operator would accept.
 *
 * Two directions and they are different mistakes, so they are different
 * messages. Too long is somebody accommodating a slow stack, which `N1-R26`
 * refuses by name. Too short is somebody who has read that clause and
 * overcorrected — a bound under a second loses a healthy stack on a busy
 * network, and an app that reports a working stack as unreachable is worse than
 * one that waits.
 */
final class ReachWaitsTooLong extends InvalidArgumentException
{
    public static function forTheOperator(int $seconds): self
    {
        return new self(sprintf(
            'A call may wait %d seconds and this one asks for %d. N1-R26 says the bound must not be raised to accommodate a slow stack: a timeout is not a budget for how long a machine may take, it is how long somebody holds a phone that is doing nothing. A stack needing longer has something wrong with it, and the app says so rather than waiting quietly.',
            Timeout::CEILING,
            $seconds,
        ));
    }

    public static function forTheStack(int $seconds): self
    {
        return new self(sprintf(
            'A call must wait at least %d second and this one asks for %d. Below that a healthy stack on a busy network is reported as unreachable, which is a worse answer than a slow one.',
            Timeout::FLOOR,
            $seconds,
        ));
    }
}
