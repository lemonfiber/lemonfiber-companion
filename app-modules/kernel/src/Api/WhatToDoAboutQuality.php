<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about how good its media is.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of its path, and this is where the reach of this app over quality is
 * readable from one file. Putting a preset back over a hand-edited
 * configuration is not a case, because its whole effect is overwriting what
 * the operator changed.
 */
enum WhatToDoAboutQuality: string
{
    /** Choose a preset, for everything or one kind of media, and confirm one that was held. */
    case Choose = 'choose';

    /** Fetch the library again at the presets in force, described first. */
    case Upgrade = 'upgrade';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: the two vocabularies are allowed to differ, and this app's word
     * for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Choose => 'quality-set',
            self::Upgrade => 'quality-upgrade',
        };
    }
}
