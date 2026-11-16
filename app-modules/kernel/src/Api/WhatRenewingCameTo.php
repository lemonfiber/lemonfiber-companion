<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What came of asking the core to renew a live stream's passes: when they now lapse, or what stood in the way. */
final readonly class WhatRenewingCameTo
{
    private function __construct(private Instant|Obstacle $answer) {}

    public static function renewedUntil(Instant $lapses): self
    {
        return new self($lapses);
    }

    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TRenewed of object
     * @template TRefused of object
     *
     * @param Closure(Instant): TRenewed  $renewed
     * @param Closure(Obstacle): TRefused $refused
     *
     * @return TRenewed|TRefused
     */
    public function either(Closure $renewed, Closure $refused): object
    {
        return $this->answer instanceof Obstacle ? $refused($this->answer) : $renewed($this->answer);
    }
}
