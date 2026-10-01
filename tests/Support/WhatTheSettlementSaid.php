<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;

/**
 * What an arm handed out, carried out of `whichever()` in one piece.
 *
 * The fold answers an object, which is what stops a reader returning a bare
 * `null` for the arms it did not think about. {@see TheWordCarriedOut} is the
 * same device for an arm that carries one word.
 */
final readonly class WhatTheSettlementSaid
{
    public function __construct(
        public string $arm,
        public Services $services,
        public ?WhoSettledIt $whose = null,
        public ?WhyItWasChosen $why = null,
    ) {}
}
