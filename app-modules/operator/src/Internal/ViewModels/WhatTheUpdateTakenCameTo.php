<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What an update taken here came to, by its own report, as the fields a screen draws. */
final readonly class WhatTheUpdateTakenCameTo
{
    /**
     * @param list<WhatOneServiceTookItSays> $applied            what became of each service it touched, those not where the operator wanted them first
     * @param int                            $didNotArrive       how many of those are not where the operator wanted them
     * @param bool                           $anythingUnanswered whether the stack cannot say what some are doing
     * @param list<AMoveInPlaceAsShown>      $moved              what became of each move in place it tried
     * @param list<AMoveInPlaceAsShown>      $guards             each of lemonfiber's own guards it moved or could not
     */
    public function __construct(
        public array $applied,
        public int $didNotArrive,
        public bool $anythingUnanswered,
        public array $moved,
        public array $guards = [],
    ) {}
}
