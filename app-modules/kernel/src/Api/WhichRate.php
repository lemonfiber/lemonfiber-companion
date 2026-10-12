<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which of the rates a download client gives for one direction a figure is.
 */
enum WhichRate: string
{
    case Asked = 'asked';

    case Accepted = 'accepted';

    case Moving = 'moving';

    /** The catalogue key a screen says it with. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line_change.rate.%s', $this->value);
    }
}
