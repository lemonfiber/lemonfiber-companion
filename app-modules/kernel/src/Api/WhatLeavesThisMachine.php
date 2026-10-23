<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Everything that leaves a machine: lemonfiber's own requests, those it makes on a plugin's behalf, and its services'.
 *
 * **Three lists, never one.** What lemonfiber asks for on its own account is
 * something this product answers for, with a switch for each; what it sends
 * for a plugin is the plugin's asking; what a service reaches is that
 * service's doing. Merged, a reader could not tell which connections they can
 * turn off here, which a plugin brought, and which they would have to take up
 * with somebody else — so there is no accessor that returns more than one.
 */
final readonly class WhatLeavesThisMachine
{
    private function __construct(
        private OurRequests $ours,
        private RequestsOnAPluginsBehalf $onBehalf,
        private TheirRequests $theirs,
    ) {}

    /** The three lists, each in the stack's order. */
    public static function of(OurRequests $ours, RequestsOnAPluginsBehalf $onBehalf, TheirRequests $theirs): self
    {
        return new self($ours, $onBehalf, $theirs);
    }

    /** Every request lemonfiber makes, in the stack's fixed order. */
    public function ours(): OurRequests
    {
        return $this->ours;
    }

    /** Every request lemonfiber makes on an installed plugin's behalf. */
    public function onBehalf(): RequestsOnAPluginsBehalf
    {
        return $this->onBehalf;
    }

    /** What each of the stack's services reaches. */
    public function theirs(): TheirRequests
    {
        return $this->theirs;
    }
}
