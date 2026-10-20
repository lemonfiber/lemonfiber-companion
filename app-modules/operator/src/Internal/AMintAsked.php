<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\AKeyToMint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Operator\Internal\ViewModels\WhatMintingShows;

/** One mint the operator asked for: of which stack, on which session, of which key, when, and what an obstacle comes to. */
final readonly class AMintAsked
{
    /** @param Closure(Obstacle): WhatMintingShows $met what stood in the way of asking the stack comes to */
    public function __construct(
        public Stack $stack,
        public Session $session,
        public AKeyToMint $key,
        public Instant $now,
        public Closure $met,
    ) {}
}
