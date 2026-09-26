<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What the stack did with a quality choice, in its own word for it.
 *
 * *Held* and *rehearsed* are the two that write nothing and could be read as
 * if they had: a held choice waits on a confirmation, and a rehearsed one is
 * what a choice would come to. Each has a sentence of its own, and neither is
 * *recorded*'s.
 */
enum WhatBecameOfTheChoice: string
{
    /** The choice was only shown; nothing was asked to change. */
    case Shown = 'shown';

    /** The choice was recorded. */
    case Recorded = 'recorded';

    /** The choice would be recorded, and this was a rehearsal, so it was not. */
    case Rehearsed = 'rehearsed';

    /** The choice needs transcoding this machine cannot do well, so it waits on a confirmation. */
    case Held = 'held';

    /** The recorded preset was put back over a hand-edited configuration. */
    case Reapplied = 'reapplied';

    /** Putting the preset back was rehearsed, and nothing was written. */
    case WouldReapply = 'would-reapply';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('quality.became.%s', $this->value);
    }
}
