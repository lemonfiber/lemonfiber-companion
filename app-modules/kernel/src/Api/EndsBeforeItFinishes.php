<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The two ways a followed outcome says the stack has no outcome for the work any more. */
trait EndsBeforeItFinishes
{
    /** The stack has no outcome for it any more, and says why. */
    public static function ended(WhyTheWorkEnded $why): self
    {
        return new self(ending: $why);
    }

    /** The stack has no outcome for it any more, and says nothing of why. */
    public static function noOutcome(): self
    {
        return new self();
    }
}
