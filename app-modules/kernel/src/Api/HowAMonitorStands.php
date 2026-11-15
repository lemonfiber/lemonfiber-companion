<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What one of the outside watcher's monitors last said.
 *
 * The value is the wire's word. A monitor that is not reporting says nothing
 * of what it covers, so it is never read as that being down.
 */
enum HowAMonitorStands: string
{
    /** What it covers answered. */
    case Up = 'up';

    /** What it covers did not answer. */
    case Down = 'down';

    /** It has not settled on an answer yet. */
    case Pending = 'pending';

    /** Somebody paused it. */
    case Paused = 'paused';

    /** What it covers is down on purpose. */
    case ExpectedDown = 'expected-down';

    /** The monitor itself has stopped reporting. */
    case NotReporting = 'not-reporting';

    /** Whether it says the opposite of what the stack says of the service it covers: down where the stack has it up, or up where the stack does not. */
    public function disagreesWith(HowAServiceRuns $stack): bool
    {
        return ($this === self::Down && $stack->cameBack()) || ($this === self::Up && ! $stack->cameBack());
    }

    /** The key for this state, on the watcher screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('watcher.state.%s', $this->value);
    }
}
