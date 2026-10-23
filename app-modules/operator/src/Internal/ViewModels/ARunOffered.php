<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhatARunCovers;

/**
 * One run of checks Health offers, as the link that leads to it.
 */
final readonly class ARunOffered
{
    /**
     * @param string         $said       the catalogue key for the link's words
     * @param string         $familySaid the catalogue key for the family it runs, or empty for every check
     * @param WhatARunCovers $covers     what the run covers, which the link leads to
     */
    public function __construct(
        public string $said,
        public string $familySaid,
        public WhatARunCovers $covers,
    ) {}
}
