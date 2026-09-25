<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A size, flattened for a template: a whole figure and the catalogue key for its unit. */
final readonly class ASizeAsShown
{
    /**
     * @param int    $figure how many of the unit
     * @param string $unit   the catalogue key for the unit
     */
    public function __construct(
        public int $figure,
        public string $unit,
    ) {}
}
