<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What each verb takes away, as the stack reported it on the reading.
 *
 * Keyed by the verb this surface offers rather than by the wire's word for it,
 * which is the mapping {@see WhatToDoWithIt::asked()} already owns: the stack
 * calls a service stop *down* and this app calls it *stop*, and a screen asking
 * for one and being answered about the other is the confusion that mapping
 * exists to prevent.
 *
 * **Every verb this surface offers has an answer, and there is no absent
 * case.** A reading short of one is a payload gone wrong rather than a verb
 * that costs nothing, and the reader refuses rather than this type
 * carry a state meaning *unknown* that a screen would render as *free*.
 */
final readonly class Disturbances
{
    private function __construct(
        private WhatItTakesAway $starting,
        private WhatItTakesAway $stopping,
        private WhatItTakesAway $restarting,
    ) {}

    public static function of(
        WhatItTakesAway $starting,
        WhatItTakesAway $stopping,
        WhatItTakesAway $restarting,
    ): self {
        return new self($starting, $stopping, $restarting);
    }

    /**
     * Say what this verb takes away, or that the stack reports nothing for it.
     *
     * A `match` over the verb rather than accessors, so a case added to
     * {@see WhatToDoWithIt} stops compiling here until somebody has said what
     * it costs — which is the same refusal the stack makes on its own side.
     *
     * A fetch takes the second arm: the stack reports what starting, stopping
     * and restarting disturb, and no bound for fetching. Two arms rather than
     * a nullable answer, so the caller says what a missing bound means instead
     * of a screen drawing it as free.
     *
     * @template TSaid of object
     * @template TNothing of object
     *
     * @param Closure(WhatItTakesAway): TSaid $said
     * @param Closure(): TNothing             $unreported
     *
     * @return TSaid|TNothing
     */
    public function forThe(WhatToDoWithIt $doing, Closure $said, Closure $unreported): object
    {
        return match ($doing) {
            WhatToDoWithIt::Start => $said($this->starting),
            WhatToDoWithIt::Stop => $said($this->stopping),
            WhatToDoWithIt::Restart => $said($this->restarting),
            WhatToDoWithIt::Pull => $unreported(),
        };
    }
}
