<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\Presenters\HowTheGuideReads;
use Modules\Household\Internal\ViewModels\WhatStoppedAMember;
use Modules\Household\Internal\ViewModels\WhatTheGuideTurnedOutToBe;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\Whose;

/**
 * The live TV guide, asked as the member and drawn on the phone's own clock from now.
 *
 * Where it cannot be had the guide says what stood in the way, and a session
 * the stack refused is let go of here, where the refusal is in hand.
 */
final readonly class TheLiveTvGuide
{
    use LetsGoOfARefusedSession;

    public function __construct(
        private SecureStorage $storage,
        private Tuning $tuning,
        private LocalZone $zone,
        private Clock $clock,
    ) {}

    /** The guide of the member signed in to the stack. */
    public function of(Stack $stack): WhatTheGuideTurnedOutToBe
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session, Whose $whose): WhatTheGuideTurnedOutToBe => $this->tuning->theGuideOf($stack, $session, $whose)->either(
                told: fn(TheGuide $guide): WhatTheGuideTurnedOutToBe => new HowTheGuideReads($this->zone->zone(), $this->clock->now())->these($guide),
                refused: $this->lettingGoIfRefused($stack, static fn(Obstacle $why): WhatTheGuideTurnedOutToBe => WhatTheGuideTurnedOutToBe::met(WhatStoppedAMember::by($why))),
            ),
            notHeld: static fn(): WhatTheGuideTurnedOutToBe => WhatTheGuideTurnedOutToBe::signedOut(),
        );
    }
}
