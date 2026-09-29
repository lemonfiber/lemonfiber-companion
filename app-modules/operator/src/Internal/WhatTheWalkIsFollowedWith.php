<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HearingTheWalk;

/**
 * The ports a screen holding the stream for a running walk reaches for, handed to it together.
 *
 * {@see HearsWhereTheWalkIs} is where they are used, and {@see WhatItListensWith}
 * is the same arrangement for the health summary.
 */
final readonly class WhatTheWalkIsFollowedWith
{
    public function __construct(
        public HearingTheWalk $hearing,
        public Clock $clock,
        public Capture $capture,
    ) {}
}
