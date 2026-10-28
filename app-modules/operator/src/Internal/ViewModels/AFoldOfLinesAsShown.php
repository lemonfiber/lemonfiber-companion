<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A run of decorative lines folded into one row that says how many, and which fold it is, counted from the top of the window. */
final readonly class AFoldOfLinesAsShown
{
    public function __construct(public int $howMany, public int $fold) {}
}
