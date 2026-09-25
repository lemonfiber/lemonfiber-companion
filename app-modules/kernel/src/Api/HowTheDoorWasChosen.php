<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the front door was worked out, named by the operator, or named and refused.
 *
 * A derived door drawn as a decision would be a decision nobody made, so each
 * is said in its own words.
 */
enum HowTheDoorWasChosen: string
{
    /** Worked out from what the stack declares. */
    case Derived = 'derived';

    /** Named by the operator, and it is the door. */
    case Named = 'named';

    /** Named by the operator and refused; the worked-out door stands. */
    case Refused = 'refused';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.front_door.chosen.%s', $this->value);
    }
}
