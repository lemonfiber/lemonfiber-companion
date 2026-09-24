<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

/** A ratio carried out of a fold: the catalogue key that says it, and the figure where there is one. */
final readonly class RatioAsShown
{
    public function __construct(public string $said, public string $ratio) {}
}
