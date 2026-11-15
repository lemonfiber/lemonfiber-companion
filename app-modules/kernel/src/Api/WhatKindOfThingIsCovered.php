<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What kind of thing a monitor covers.
 *
 * The value is the wire's key.
 */
enum WhatKindOfThingIsCovered: string
{
    /** A service of the stack. */
    case Service = 'service';

    /** A way into the stack the household uses. */
    case EntryPoint = 'entry-point';

    /** A job the stack runs. */
    case Job = 'job';

    /** The key naming a thing of this kind, on the watcher screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('watcher.covers.%s', $this->value);
    }
}
