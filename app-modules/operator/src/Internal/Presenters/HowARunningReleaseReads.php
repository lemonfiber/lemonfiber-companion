<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Release;
use Modules\Operator\Internal\ViewModels\WhatTheStackIsOn;

/**
 * Turning what a reading says about the version in use into the word a line draws.
 *
 * {@see \Modules\Kernel\Api\Upkeep::running()} answers in two closure arms
 * rather than with a nullable, which is `C2`'s cure and right: a screen handed
 * a null would print an empty version where one belongs, and an operator would
 * read that as *it is running nothing*. Both arms have to hand back an object,
 * and what the screen wants out of them is a string — so there is a method here
 * for each arm, and the arm with no release to read is the one that still has
 * something to say.
 */
final readonly class HowARunningReleaseReads
{
    public function of(Release $release): WhatTheStackIsOn
    {
        return new WhatTheStackIsOn($release->version());
    }

    /** The stack has not said what it is on, which is not the same as nothing. */
    public function notNamed(): WhatTheStackIsOn
    {
        return new WhatTheStackIsOn(WhatTheStackIsOn::NOT_NAMED);
    }
}
