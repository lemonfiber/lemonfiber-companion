<?php

declare(strict_types=1);

namespace Modules\Updates\Internal;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Upkeep;

/** A reading of where a stack stands, and when it was read: what a kept reading always is. */
final readonly class AnUpkeepAsOf
{
    public function __construct(public Upkeep $upkeep, public Instant $readAt) {}
}
