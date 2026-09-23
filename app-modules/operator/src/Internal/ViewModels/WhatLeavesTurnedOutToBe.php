<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what leaves it produced, flattened for a template.
 *
 * **Two lists, as the answer has them.** The template draws them under two
 * headings and there is no field holding both, so nothing downstream can merge
 * what lemonfiber sends with what a service sends.
 */
final readonly class WhatLeavesTurnedOutToBe
{
    /**
     * @param list<OneOfOurRequests>   $ours   every request lemonfiber makes, in the stack's order
     * @param list<OneOfTheirRequests> $theirs what each service reaches, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $ours,
        public array $theirs,
    ) {}
}
