<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a stack did about each repair it was agreed to, flattened for a template. */
final readonly class WhatTheMendingCameTo
{
    /**
     * @param list<WhatOneOutcomeSays> $outcomes what became of each repair, in the stack's order
     * @param int                      $changed  how many of them changed anything on the machine
     */
    public function __construct(
        public array $outcomes,
        public int $changed,
    ) {}
}
