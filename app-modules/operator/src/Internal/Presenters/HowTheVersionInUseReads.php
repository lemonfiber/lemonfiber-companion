<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Release;
use Modules\Operator\Internal\ViewModels\WhatTheStackIsOn;

/**
 * Turning what a reading says about the release in use into what a screen draws.
 *
 * {@see \Modules\Kernel\Api\Upkeep::inUse()} answers in two closure arms rather
 * than with a nullable, which is `C2`'s cure: a screen handed a null would print
 * an empty version where one belongs, and an operator would read that as *it is
 * running nothing*. Both arms hand back an object, so there is a method here
 * for each, and the arm with no release to read still has something to say.
 */
final readonly class HowTheVersionInUseReads
{
    public function of(Release $inUse): WhatTheStackIsOn
    {
        return new WhatTheStackIsOn($inUse->version(), new HowAReleaseReads()->of($inUse));
    }

    /** The stack has not said what it is on, which is not the same as nothing. */
    public function notNamed(): WhatTheStackIsOn
    {
        return new WhatTheStackIsOn(WhatTheStackIsOn::NOT_NAMED);
    }
}
