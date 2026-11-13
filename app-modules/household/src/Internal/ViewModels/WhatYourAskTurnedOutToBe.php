<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/** What reading one of a member's asks came to: the ask, none by that reference among what they asked, what stopped the reading, or no session to read with. */
final readonly class WhatYourAskTurnedOutToBe
{
    private function __construct(
        public bool $cameBack,
        public ?WhatYourAskSays $ask,
        public ?WhatStoppedAMember $stopped,
    ) {}

    public static function found(WhatYourAskSays $ask): self
    {
        return new self(cameBack: true, ask: $ask, stopped: null);
    }

    public static function notAmongThem(): self
    {
        return new self(cameBack: true, ask: null, stopped: null);
    }

    public static function somethingStopped(Obstacle $why): self
    {
        return new self(cameBack: false, ask: null, stopped: WhatStoppedAMember::by($why));
    }

    public static function theSessionEnded(): self
    {
        return new self(cameBack: false, ask: null, stopped: null);
    }
}
