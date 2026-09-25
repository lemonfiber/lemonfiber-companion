<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** How much of a traced series is here, flattened for a template. */
final readonly class ASeriesAsShown
{
    /** @param list<ASeasonAsShown> $seasons each season, in order */
    public function __construct(
        public int $have,
        public int $wanted,
        public int $unmonitored,
        public array $seasons,
    ) {}
}
