<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a plugin's change replaced, as a template draws it under the row.
 *
 * The line saying what was there, and where that came from, folded the same
 * way the row's own origin is.
 */
final readonly class WhatWasThereBefore
{
    /**
     * @param string                  $said  the catalogue key for the line: a value, nothing set, or withheld
     * @param string                  $value the value it replaced, or empty where there is none to show
     * @param WhereARowSaysItCameFrom $from  where the replaced value came from
     */
    public function __construct(
        public string $said,
        public string $value,
        public WhereARowSaysItCameFrom $from,
    ) {}
}
