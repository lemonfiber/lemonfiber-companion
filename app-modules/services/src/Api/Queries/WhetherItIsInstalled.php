<?php

declare(strict_types=1);

namespace Modules\Services\Api\Queries;

use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\HowAServiceRuns;

/**
 * Whether a stack has a service installed, or could run it and nothing asked for it.
 *
 * Installed is anything the stack runs in any state, and anything a running
 * form brought in, absent or not: an absence a form asked for is something
 * wrong. A service that is absent and that no form brought in is one nobody
 * wanted, and nothing is wrong with it.
 */
final readonly class WhetherItIsInstalled
{
    public function of(Daemon $daemon): bool
    {
        return $daemon->runs() !== HowAServiceRuns::Absent || $daemon->whatBroughtItIn()->count() > 0;
    }
}
