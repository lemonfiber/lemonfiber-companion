<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which of the two volumes a reading is about.
 *
 * Both are reported whether or not they are one drive, because either filling
 * stops the stack, and in different ways.
 */
enum WhatAVolumeHolds: string
{
    /** Where the media and the downloads live. */
    case Data = 'data';

    /** Where the services keep their own configuration and databases. */
    case Services = 'services';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.room.holds.%s', $this->value);
    }
}
