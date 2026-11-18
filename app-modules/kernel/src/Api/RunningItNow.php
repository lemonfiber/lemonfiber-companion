<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Running one run-and-exit service at once, whether or not one of its times fell due.
 */
enum RunningItNow: string implements AnAction
{
    case Run = 'run';

    public function asked(): string
    {
        return $this->value;
    }
}
