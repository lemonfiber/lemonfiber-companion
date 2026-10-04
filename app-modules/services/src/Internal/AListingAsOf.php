<?php

declare(strict_types=1);

namespace Modules\Services\Internal;

use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Instant;

/** A listing of what a stack runs, when it was read, and the moment it was handed back. */
final readonly class AListingAsOf
{
    public function __construct(public Daemons $daemons, public Instant $readAt, public Instant $now) {}
}
