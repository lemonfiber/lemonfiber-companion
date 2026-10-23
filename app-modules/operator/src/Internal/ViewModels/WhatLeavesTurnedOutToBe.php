<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function array_any;

/**
 * What asking a stack what leaves it produced, flattened for a template.
 *
 * **Three lists, as the answer has them.** The template draws them under
 * three headings and there is no field holding more than one, so nothing
 * downstream can merge what lemonfiber sends, what it sends for a plugin and
 * what a service sends.
 */
final readonly class WhatLeavesTurnedOutToBe
{
    /**
     * @param list<OneOfOurRequests>           $ours     every request lemonfiber makes, in the stack's order
     * @param list<OneRequestOnAPluginsBehalf> $onBehalf every request it makes on a plugin's behalf, in the stack's order
     * @param list<OneOfTheirRequests>         $theirs   what each service reaches, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $ours,
        public array $onBehalf,
        public array $theirs,
    ) {}

    /**
     * Whether any service is not the stack's own, which is when the list says
     * once what an unmarked row is.
     */
    public function marksAnOrigin(): bool
    {
        return array_any($this->theirs, static fn(OneOfTheirRequests $request): bool => ! $request->from->isTheStacksOwn());
    }
}
