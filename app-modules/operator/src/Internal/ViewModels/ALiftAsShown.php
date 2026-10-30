<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a lift of the line's limits stands, flattened for a template. */
final readonly class ALiftAsShown
{
    /**
     * @param string $said  the catalogue key for where it stands
     * @param int    $count how many of its unit that key counts
     * @param string $says  what it amounts to in the stack's words, blank where it gave none
     */
    public function __construct(
        public string $said,
        public int $count,
        public string $says,
    ) {}
}
