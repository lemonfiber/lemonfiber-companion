<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What may be done about a setup already here: the modes, what adopting each service would come to, and what no mode carries.
 *
 * Apart from what was found, because these belong to choosing a mode rather
 * than to the look that comes before one.
 */
final readonly class WhatMayBeDone
{
    private function __construct(
        private TheModes $modes,
        private WhatAdoptingWouldDo $carrying,
        private WhatIsUnsupported $notCarried,
    ) {}

    /** What the stack offers, as it offered it. */
    public static function offered(TheModes $modes, WhatAdoptingWouldDo $carrying, WhatIsUnsupported $notCarried): self
    {
        return new self($modes, $carrying, $notCarried);
    }

    /** Every mode, in the stack's order. */
    public function modes(): TheModes
    {
        return $this->modes;
    }

    /** What adopting each recognised service would come to. */
    public function carrying(): WhatAdoptingWouldDo
    {
        return $this->carrying;
    }

    /** What no mode carries across, whichever runs. */
    public function notCarried(): WhatIsUnsupported
    {
        return $this->notCarried;
    }
}
