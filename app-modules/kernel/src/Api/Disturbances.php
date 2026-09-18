<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

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
     * What this verb takes away.
     *
     * A `match` over the verb rather than three accessors, so a case added to
     * {@see WhatToDoWithIt} stops compiling here until somebody has said what
     * it costs — which is the same refusal the stack makes on its own side.
     */
    public function forThe(WhatToDoWithIt $doing): WhatItTakesAway
    {
        return match ($doing) {
            WhatToDoWithIt::Start => $this->starting,
            WhatToDoWithIt::Stop => $this->stopping,
            WhatToDoWithIt::Restart => $this->restarting,
        };
    }
}
