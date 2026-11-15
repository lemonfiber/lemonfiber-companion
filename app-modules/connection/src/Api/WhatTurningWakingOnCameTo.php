<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Closure;
use Modules\Kernel\Api\ADistributor;

/** What turning waking on for a stack came to: on through a distributor, or off, and why. */
final readonly class WhatTurningWakingOnCameTo
{
    private function __construct(private ADistributor|WhyWakingStayedOff $came) {}

    public static function on(ADistributor $through): self
    {
        return new self($through);
    }

    public static function off(WhyWakingStayedOff $why): self
    {
        return new self($why);
    }

    /**
     * @template TOn of object
     * @template TOff of object
     *
     * @param  Closure(ADistributor): TOn      $on
     * @param  Closure(WhyWakingStayedOff): TOff $off
     * @return TOn|TOff
     */
    public function either(Closure $on, Closure $off): object
    {
        return $this->came instanceof ADistributor ? $on($this->came) : $off($this->came);
    }
}
