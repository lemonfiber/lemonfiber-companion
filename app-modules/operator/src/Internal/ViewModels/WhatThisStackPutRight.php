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
 * Every field but the first defaults, and each fold in
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
     * @param string                   $met       the key for what stood in the way, or empty
     * @param string                   $remedy    the key for what to do about it, or empty
     * @param bool                     $isSignedOut whether the stack refused this device's session
     */
    public function __construct(
        public bool $isWorking = false,
        public bool $hasEnded = false,
        public array $outcomes = [],
        public int $changed = 0,
        public string $met = '',
        public string $remedy = '',
        public bool $isSignedOut = false,
    ) {}
}
