<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A repair arrived naming something it affects, with nothing named.
 *
 * Raised where a string becomes one of a repair's effects, for the reason
 * {@see RemedySaysNothing} gives: this is a value that cannot be constructed
 * rather than a refusal crossing a boundary, so there is nothing for a caller
 * to open (C1, C3).
 *
 * A blank effect is worse than a missing one. `N2-R4` asks the app to state
 * what else a repair affects, and a list with an empty row in it renders as a
 * bullet with nothing beside it — which reads as the app having lost something
 * rather than as the stack having said nothing.
 */
final class EffectSaysNothing extends InvalidArgumentException
{
    public static function inARepair(): self
    {
        return new self('A repair named something it affects and the name was blank, which renders as an empty bullet where a consequence belongs.');
    }
}
