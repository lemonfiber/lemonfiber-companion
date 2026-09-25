<?php

declare(strict_types=1);

namespace Tests\Support;

use function __;
use function is_string;

use Modules\Kernel\Api\WhatItWouldNeed;

/** What a service left out would need, as the catalogue says it. */
final readonly class WhatANeedSays
{
    public static function of(WhatItWouldNeed $needs): string
    {
        $said = __($needs->saidOnTheScreen());

        return is_string($said) ? $said : $needs->saidOnTheScreen();
    }
}
