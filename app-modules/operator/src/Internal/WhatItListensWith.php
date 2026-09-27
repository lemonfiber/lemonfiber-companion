<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Hearing;

/**
 * The three ports a screen holding the stream reaches for, handed to it together.
 *
 * {@see HearsHowTheStackIs} is where they are used, and a screen is where they
 * arrive, so the screen hands them over in one value rather than the trait
 * reaching for fields it cannot declare.
 */
final readonly class WhatItListensWith
{
    public function __construct(
        public Hearing $hearing,
        public Clock $clock,
        public Capture $capture,
    ) {}
}
