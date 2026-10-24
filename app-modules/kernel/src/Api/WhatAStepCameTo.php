<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What one step of a recipe came to when it ran, as the stack words it.
 */
enum WhatAStepCameTo: string
{
    /** The call was made and answered as it should be. */
    case Answered = 'answered';

    /** Its guard said not to make it. */
    case Skipped = 'skipped';

    /** An earlier step stopped the recipe before it. */
    case NotReached = 'not-reached';

    /** Where it calls could not be reached. */
    case Unreachable = 'unreachable';

    /** It was refused, such as a name outside the stack answering from inside it. */
    case Refused = 'refused';

    /** It would have carried a value nobody approved, so it was not made. */
    case Withheld = 'withheld';

    /** It was answered with something it did not expect. */
    case Unexpected = 'unexpected';

    /** It was answered without what it was to capture. */
    case Uncaptured = 'uncaptured';

    /** Its answer was larger than a recipe may read. */
    case Oversized = 'oversized';

    /** The catalogue key a screen says it with. */
    public function saidOnTheScreen(): string
    {
        return sprintf('plugins.run.came.%s', $this->value);
    }
}
