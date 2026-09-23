<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a figure for the line came from.
 *
 * Declared is a claim and observed is a measurement, and somebody deciding a
 * cap on a declared figure is deciding on a guess — so which it is travels with
 * the figure everywhere it is shown.
 */
enum HowTheLineWasMeasured: string
{
    /** The operator gave it, presumably off the plan they pay for. */
    case Declared = 'declared';

    /** The fastest the stack has been seen to move. */
    case Observed = 'observed';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.measured.%s', $this->value);
    }
}
