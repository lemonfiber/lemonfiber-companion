<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What running a run-and-exit service at once came to, as the schedule screen draws it: how the run ended, or the stack's reason nothing ran.
 */
final readonly class WhatRanNowAsShown
{
    public function __construct(
        public string $name,
        public string $healthSaid,
        public string $tone,
        public ALastRunAsShown $last,
        public string $held,
    ) {}
}
