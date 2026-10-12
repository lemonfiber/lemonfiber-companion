<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One direction on one download client, flattened for a template. */
final readonly class ADirectionHeldAsShown
{
    /**
     * @param string             $said        the catalogue key naming the direction, filled with the verdict
     * @param string             $verdictSaid the catalogue key for what became of the limit
     * @param bool               $miss        whether the client is not keeping to what it was given
     * @param list<ARateAsShown> $rates       what it was asked to hold to, reports in force and moves now, each where it said
     */
    public function __construct(
        public string $said,
        public string $verdictSaid,
        public bool $miss,
        public array $rates,
    ) {}
}
