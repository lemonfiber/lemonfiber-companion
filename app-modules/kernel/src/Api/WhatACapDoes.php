<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What happens when a declared monthly cap is reached.
 *
 * Three, and they are three different evenings: *you have reached your cap*
 * without which one is not an answer.
 */
enum WhatACapDoes: string
{
    /** Stop fetching until the month turns over. */
    case Pause = 'pause';

    /** Keep going, slowly, so what is half-finished can finish. */
    case Throttle = 'throttle';

    /** Carry on; some caps cost money and some only cost speed. */
    case Continue = 'continue';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.cap.%s', $this->value);
    }
}
