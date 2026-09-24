<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a month stands against a declared cap.
 */
enum WhereTheMonthStands: string
{
    /** Comfortably inside it. */
    case Within = 'within';

    /** Close enough that there is still time to do something. */
    case Warning = 'warning';

    /** Reached, and the declared behaviour applies. */
    case Exceeded = 'exceeded';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.month.%s', $this->value);
    }
}
