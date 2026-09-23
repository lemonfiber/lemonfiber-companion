<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Everything that leaves a machine: lemonfiber's own requests, and its services'.
 *
 * **Two lists, never one.** What lemonfiber asks for on its own account is
 * something this product answers for, with a switch for each; what a service
 * reaches is that service's doing. Merged, a reader could not tell which
 * connections they can turn off here and which they would have to take up
 * with somebody else — so there is no accessor that returns both.
 */
final readonly class WhatLeavesThisMachine
{
    private function __construct(private OurRequests $ours, private TheirRequests $theirs) {}

    /** Both lists, each in the stack's order. */
    public static function of(OurRequests $ours, TheirRequests $theirs): self
    {
        return new self($ours, $theirs);
    }

    /** Every request lemonfiber makes, in the stack's fixed order. */
    public function ours(): OurRequests
    {
        return $this->ours;
    }

    /** What each of the stack's services reaches. */
    public function theirs(): TheirRequests
    {
        return $this->theirs;
    }
}
