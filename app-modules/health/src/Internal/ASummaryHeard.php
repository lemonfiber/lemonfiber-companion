<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheHealthSummary;

/**
 * A summary, when it arrived, and whether it still stands, which a screen
 * never shows apart.
 *
 * It stands only while the subscription that carried it is open, so it is
 * heard current and stops being current exactly once.
 */
final readonly class ASummaryHeard
{
    private function __construct(
        public TheHealthSummary $summary,
        public Instant $at,
        public bool $current,
    ) {}

    public static function at(TheHealthSummary $summary, Instant $at): self
    {
        return new self($summary, $at, current: true);
    }

    public function noLongerCurrent(): self
    {
        return new self($this->summary, $this->at, current: false);
    }
}
