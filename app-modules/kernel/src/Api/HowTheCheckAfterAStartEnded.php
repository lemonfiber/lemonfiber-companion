<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What the check the stack runs after the machine starts came to.
 *
 * The value is the wire's word.
 */
enum HowTheCheckAfterAStartEnded: string
{
    /** The stack came back, its tunnel and forwarded port included. */
    case Passed = 'passed';

    /** The stack, or its tunnel, did not come back. */
    case Failed = 'failed';

    /** The rest came back, and a part waits for its install from this build. */
    case WaitingForInstall = 'waiting-for-install';

    /** The key for what it came to, on the restart section. */
    public function saidOnTheScreen(): string
    {
        return sprintf('restarts.ended.%s', $this->value);
    }
}
