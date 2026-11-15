<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which way a monitor reaches what it covers: the way the household does, or from inside the stack.
 *
 * The value is the wire's word.
 */
enum WhichWayItLooks: string
{
    /** The way the household reaches it. */
    case Household = 'household';

    /** From inside the stack. */
    case Internal = 'internal';

    /** The key for this path, on the watcher screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('watcher.path.%s', $this->value);
    }
}
