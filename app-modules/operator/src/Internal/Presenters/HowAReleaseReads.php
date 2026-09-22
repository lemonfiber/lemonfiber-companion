<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Release;
use Modules\Operator\Internal\ViewModels\WhatOneReleaseSays;

/**
 * One release, as the row a screen offers it on.
 *
 * The sibling of {@see HowAStalledItemReads} and written the same way: Blade has
 * no `either()` and cannot be given one, so the folding happens here and the
 * template reads fields.
 */
final readonly class HowAReleaseReads
{
    public function of(Release $release): WhatOneReleaseSays
    {
        return $release->delivers()->either(
            said: static fn(string $prose): WhatOneReleaseSays => new WhatOneReleaseSays(
                version: $release->version(),
                theHouseholdWouldNotice: $release->theHouseholdWouldNotice(),
                release: $release,
                deliversSaid: $prose,
            ),
            saidNothing: static fn(): WhatOneReleaseSays => new WhatOneReleaseSays(
                version: $release->version(),
                theHouseholdWouldNotice: $release->theHouseholdWouldNotice(),
                release: $release,
            ),
        );
    }
}
