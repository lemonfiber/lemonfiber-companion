<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what it keeps produced, flattened for a template.
 *
 * The first of two readings: what the stack keeps, and whether it answered at
 * all. The copies are the second, read on a frame of their own, with an outcome
 * of their own in {@see TheCopiesAsFound}.
 */
final readonly class WhatIsKeptTurnedOutToBe
{
    /**
     * @param list<ARootAsShown>            $roots  the directories it all sits under, in the stack's order
     * @param list<SomethingKeptAsShown>    $kept   each thing kept, in the stack's order
     * @param list<SomethingBesideAsShown>  $beside what is here and is not the stack's
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $roots,
        public array $kept,
        public array $beside,
    ) {}
}
