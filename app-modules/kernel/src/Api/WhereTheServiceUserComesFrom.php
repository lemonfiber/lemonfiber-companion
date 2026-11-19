<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where the user and group the stack's services run as come from, as the stack says it.
 */
enum WhereTheServiceUserComesFrom: string
{
    case Set = 'set';

    case PartSet = 'part-set';

    case StackOwner = 'stack-owner';

    /** The key for the line saying where they come from. */
    public function saidOnTheScreen(): string
    {
        return sprintf('settled.runs_as_from.%s', $this->value);
    }
}
