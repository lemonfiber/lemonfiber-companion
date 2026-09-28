<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\Instant;

/**
 * A step a walk said, when it was heard, and whether it still stands, which a
 * screen never shows apart.
 *
 * {@see ASummaryHeard}'s shape: it stands only while the subscription that
 * carried it is open, so it is heard current and stops being current exactly
 * once.
 */
final readonly class AStepHeard
{
    private function __construct(
        public ALineItSaid $line,
        public Instant $at,
        public bool $current,
    ) {}

    public static function at(ALineItSaid $line, Instant $at): self
    {
        return new self($line, $at, current: true);
    }

    public function noLongerCurrent(): self
    {
        return new self($this->line, $this->at, current: false);
    }
}
