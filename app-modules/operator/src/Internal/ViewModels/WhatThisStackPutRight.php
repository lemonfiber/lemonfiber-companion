<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What carrying out an agreement came to, flattened for a template.
 *
 * The sibling of {@see WhatTheStackWouldPutRight}, and separate from it for the
 * reason {@see \Modules\Kernel\Api\HowTheRepairIsGoing} is separate from
 * {@see \Modules\Kernel\Api\HowTheOfferIsGoing}: one is a listing of what a
 * machine *would* do and the other a record of what it *did*, and a type
 * holding either would be one a template has to ask which it is looking at.
 *
 * The reading it belongs to comes first and does not default: a fold saying
 * nothing about whether the stack answered is a fold whose state a template
 * cannot draw. Every field after it defaults, and each fold in
 * {@see \Modules\Operator\Internal\Presenters\HowAMendingReads} says only what
 * its own state means — {@see \Modules\Kernel\Api\Size}'s rule, and the cure
 * for the eighteen unreadable arguments mutation testing found in the fold
 * beside this one.
 */
final readonly class WhatThisStackPutRight
{
    /**
     * @param bool                     $isWorking whether the stack is still carrying it out
     * @param bool                     $hasEnded  whether the stack has forgotten the job
     * @param list<WhatOneOutcomeSays> $outcomes  what became of each repair, in the stack's order
     * @param int                      $changed   how many of them changed anything on the machine
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking = false,
        public bool $hasEnded = false,
        public array $outcomes = [],
        public int $changed = 0,
    ) {}
}
