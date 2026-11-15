<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What became of one act a healing run took.
 *
 * The value is the wire's word.
 */
enum WhatBecameOfAnAct: string
{
    /** It was done. */
    case Done = 'done';

    /** It would have been done; the run was a rehearsal. */
    case Would = 'would';

    /** It was not done. */
    case Refused = 'refused';

    /** Something weaker was done in its place. */
    case FellBack = 'fell-back';

    /** The key for this outcome, on the queue screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('queue.outcome.%s', $this->value);
    }
}
