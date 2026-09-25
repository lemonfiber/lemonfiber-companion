<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** One thing a finished walkthrough hands the operator on to. */
enum WhatToDoNext: string
{
    /** Add more content, now that the shape of it is understood. */
    case MoreContent = 'more-content';

    /** Let the rest of the household ask for things themselves. */
    case Household = 'household';

    /** Watch it somewhere other than the machine it runs on. */
    case ClientApps = 'client-apps';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.walkthrough.next.%s', $this->value);
    }
}
