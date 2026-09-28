<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What sort of thing one line of what a removal reaches is.
 */
enum WhatSortItIs: string
{
    /** A container the engine is holding. */
    case Container = 'container';

    /** A network the containers were on. */
    case Network = 'network';

    /** An image that was pulled. */
    case Image = 'image';

    /** A directory or a file on the machine. */
    case Path = 'path';

    /** The catalogue key for this sort of thing, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('uninstall.sort.%s', $this->value);
    }
}
