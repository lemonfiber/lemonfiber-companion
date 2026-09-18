<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a reading of the stack's upkeep came away with.
 *
 * The same shape {@see WhatIsRunning} has, for the same reason: `C1` wants a
 * stack that is asleep, one on another network and one whose session has ended
 * to be states of the world rather than exceptions, and the
 * operator told which of them they met. A reading that raised would make the
 * screen's ordinary case an error path.
 */
final readonly class WhatIsCurrent
{
    private function __construct(private Upkeep|Obstacle $answer) {}

    public static function stands(Upkeep $upkeep): self
    {
        return new self($upkeep);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * @template TStands of object
     * @template TMet of object
     *
     * @param  Closure(Upkeep): TStands  $stands
     * @param  Closure(Obstacle): TMet  $met
     * @return TStands|TMet
     */
    public function either(Closure $stands, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $stands($this->answer);
    }
}
