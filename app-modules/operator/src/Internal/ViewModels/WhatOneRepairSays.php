<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One repair a stack offered, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Repair} publishes its three clauses through one
 * closure rather than three accessors, because the three statements are
 * one requirement and three getters are three chances to call two of them.
 * Blade cannot call a closure, so the fold happens once per row in
 * {@see \Modules\Operator\Internal\Presenters\HowARepairReads} —
 * {@see WhatOneFindingSays}' shape, and the reason
 * that class exists.
 */
final readonly class WhatOneRepairSays
{
    /**
     * @param string       $does      what the repair would do, in the stack's own words
     * @param list<string> $effects   what else it touches, which may be nothing
     * @param string       $undoing   the key for whether it can be taken back
     * @param string       $answers   which check it belongs under, for grouping it
     */
    public function __construct(
        public string $does,
        public array $effects,
        public string $undoing,
        public string $answers,
    ) {}
}
