<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A place removing everything could not take, and why, flattened for a template. */
final readonly class APlaceLeftAsShown
{
    public function __construct(
        public string $where,
        public string $why,
    ) {}
}
