<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the stack says is being copied on it now: one backup or restore, or nothing.
 */
final readonly class WhatIsBeingCopied
{
    private function __construct(private ?ACopyRunning $running) {}

    /** No backup or restore is running. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /** This one is. */
    public static function running(ACopyRunning $running): self
    {
        return new self($running);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template R of object
     *
     * @param Closure(): R              $nothing
     * @param Closure(ACopyRunning): R  $running
     *
     * @return R
     */
    public function either(Closure $nothing, Closure $running): object
    {
        return $this->running instanceof ACopyRunning ? $running($this->running) : $nothing();
    }
}
