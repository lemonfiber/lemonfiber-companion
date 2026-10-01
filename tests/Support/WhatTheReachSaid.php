<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatSettledIt;

/**
 * Everything an arm handed out, carried out of `whichever()` in one piece.
 *
 * One carrier rather than a fold per assertion, so every parameter the union
 * hands a reader is read by something — an arm whose payload no test touches is
 * an arm nothing is holding to its signature.
 */
final readonly class WhatTheReachSaid
{
    public function __construct(
        public string $arm,
        public string $subject,
        public Services $services,
        public ?WhatSettledIt $settled,
        public string $why,
    ) {}
}
