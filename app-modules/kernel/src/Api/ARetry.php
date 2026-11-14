<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A delivery the core tries again: which attempt comes next, of how many, and when. */
final readonly class ARetry
{
    public function __construct(
        public int $attempt,
        public int $of,
        public Instant $next,
    ) {}
}
