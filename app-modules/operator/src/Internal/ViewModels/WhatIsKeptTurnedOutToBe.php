<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what it keeps produced, flattened for a template.
 *
 * Two readings, one after the other. `$went` is the first: what the stack
 * keeps, and whether it answered at all. `$copies` is the second, with an
 * outcome of its own. A stack can say what it keeps and still fail to list
 * its archives, and an empty list is a different answer from one that could
 * not be read.
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
        public TheCopiesAsFound $copies,
    ) {}
}
