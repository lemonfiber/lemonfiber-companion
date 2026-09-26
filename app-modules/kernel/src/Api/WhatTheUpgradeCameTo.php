<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What an upgrade came to, or the reason the asking did not complete.
 *
 * The refused arm does not say nothing was fetched, for
 * {@see WhatTheStackMadeOfIt}'s reason: from here that is not known.
 */
final readonly class WhatTheUpgradeCameTo
{
    private function __construct(private TheUpgrade|Obstacle $answer) {}

    /** The stack answered, and this is the upgrade. */
    public static function said(TheUpgrade $upgrade): self
    {
        return new self($upgrade);
    }

    /** The asking did not complete, and this is what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSaid of object
     * @template TMet of object
     *
     * @param Closure(TheUpgrade): TSaid $said
     * @param Closure(Obstacle): TMet    $met
     *
     * @return TSaid|TMet
     */
    public function either(Closure $said, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $said($this->answer);
    }
}
