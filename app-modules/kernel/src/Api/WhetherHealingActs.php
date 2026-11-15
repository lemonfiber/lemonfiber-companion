<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the queue's healing acts on what has struck out, or only classifies it.
 *
 * The value is the wire's word.
 */
enum WhetherHealingActs: string
{
    /** It acts on what has struck out. */
    case On = 'on';

    /** It only classifies. */
    case Off = 'off';

    /** The key for this switch, on the queue screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('queue.healing.%s', $this->value);
    }
}
