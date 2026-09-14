<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Release;

/**
 * One release, flattened to what a row draws.
 *
 * The sibling of {@see WhatOneStalledItemSays} and written the same way: Blade
 * has no `either()` and cannot be given one, so the folding happens here and
 * the template reads fields.
 *
 * **Whether the household would notice travels as a flag, not as a sentence.**
 * `N2-R16`'s distinction is what a screen sorts and leads on, and a row handed
 * a finished phrase could not be grouped by it.
 */
final readonly class WhatOneReleaseSays
{
    private function __construct(
        public string $version,
        public bool $theHouseholdWouldNotice,
    ) {}

    public static function of(Release $release): self
    {
        return new self($release->version(), $release->theHouseholdWouldNotice());
    }
}
