<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Running a check the stack leaves unverified because it disturbs something.
 *
 * One act, sent as `diagnose` narrowed to that check with the checks that
 * disturb included.
 */
enum CheckingWhatDisturbs: string implements AnAction
{
    case Run = 'diagnose';

    public function asked(): string
    {
        return $this->value;
    }
}
