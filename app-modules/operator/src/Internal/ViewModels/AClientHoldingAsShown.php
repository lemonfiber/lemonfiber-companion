<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One download client and what became of its limits, flattened for a template. */
final readonly class AClientHoldingAsShown
{
    /**
     * @param string                      $name        the client, by the name the stack knows it under
     * @param string                      $pullingSaid the catalogue key for whether it is fetching, blank where nothing said
     * @param bool                        $answered    whether it answered for both directions
     * @param list<ADirectionHeldAsShown>  $directions its download and its upload, where it answered
     * @param string                      $said        what it said instead of answering, blank where it answered
     */
    public function __construct(
        public string $name,
        public string $pullingSaid,
        public bool $answered,
        public array $directions,
        public string $said,
    ) {}
}
