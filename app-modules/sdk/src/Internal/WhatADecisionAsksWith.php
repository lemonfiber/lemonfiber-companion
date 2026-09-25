<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The arguments one decision is sent with, carried out of a fold.
 *
 * {@see \Modules\Kernel\Api\Decided::why()} answers through a pair of
 * closures, both of which must produce an object — `D3` refuses a `mixed` on an
 * `Api` signature, and `C2` refuses a null, so the two arms cannot simply hand
 * back an array and nothing. This is what they hand back instead, and what
 * {@see \Modules\Kernel\Api\WhatToWalk::either()} hands back too: a title to
 * walk, or nothing named for the stack to choose from.
 *
 * Here rather than in the kernel, because the shape inside it is the wire's:
 * which fields a stack expects for an action is the SDK's business, and a
 * kernel type carrying them would be the contract leaking one module inwards.
 */
final readonly class WhatADecisionAsksWith
{
    /** @param array<string, int|string> $said what the action is asked with */
    public function __construct(public array $said) {}
}
