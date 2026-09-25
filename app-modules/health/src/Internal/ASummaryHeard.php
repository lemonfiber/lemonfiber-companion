<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheHealthSummary;

/** A summary, and when it arrived, which a screen never shows apart. */
final readonly class ASummaryHeard
{
    public function __construct(
        public TheHealthSummary $summary,
        public Instant $at,
    ) {}
}
