<?php

declare(strict_types=1);

namespace Modules\Requests\Internal;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Requested;

/** What the household asked a stack for, when it was read, and the moment it was handed back. */
final readonly class RequestsAsOf
{
    public function __construct(public Requested $requested, public Instant $readAt, public Instant $now) {}
}
