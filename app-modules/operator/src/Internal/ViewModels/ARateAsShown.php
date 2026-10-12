<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One rate a download client gave for one direction, flattened for a template. */
final readonly class ARateAsShown
{
    /**
     * @param string $said   the catalogue key saying which rate it is
     * @param int    $figure the rate, rounded in its unit
     * @param string $unit   the catalogue key of the unit, in what a line is sold in
     */
    public function __construct(
        public string $said,
        public int $figure,
        public string $unit,
    ) {}
}
