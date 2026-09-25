<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** How much of one season is here, flattened for a template. */
final readonly class ASeasonAsShown
{
    /** @param list<AnEpisodeAsShown> $outstanding the wanted episodes not here yet */
    public function __construct(
        public int $season,
        public int $have,
        public int $wanted,
        public int $unmonitored,
        public array $outstanding,
    ) {}
}
