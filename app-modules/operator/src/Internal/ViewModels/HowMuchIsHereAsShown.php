<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** How much of a traced item is here, flattened for a template: a series counted, or nothing to count for a whole item. */
final readonly class HowMuchIsHereAsShown
{
    public function __construct(public ?ASeriesAsShown $series) {}
}
