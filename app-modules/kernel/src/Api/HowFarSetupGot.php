<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How far setup got on a stack, as the stack says it.
 */
enum HowFarSetupGot: string
{
    case None = 'none';

    case PartWay = 'part-way';

    case Done = 'done';

    /** Whether setup is finished. */
    public function isDone(): bool
    {
        return $this === self::Done;
    }
}
