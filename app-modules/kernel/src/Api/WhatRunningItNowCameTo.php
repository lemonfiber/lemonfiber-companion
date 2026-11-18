<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking a run-and-exit service to run at once came to: the run, as it ended, or the stack's reason nothing ran.
 */
final readonly class WhatRunningItNowCameTo
{
    private function __construct(private ItsLastRun|string $answer) {}

    /** It ran, and ended so. */
    public static function ran(ItsLastRun $run): self
    {
        return new self($run);
    }

    /** Nothing ran, for this reason in the stack's words. */
    public static function held(string $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TRan of object
     * @template THeld of object
     *
     * @param Closure(ItsLastRun): TRan $ran
     * @param Closure(string): THeld    $held
     *
     * @return TRan|THeld
     */
    public function either(Closure $ran, Closure $held): object
    {
        return $this->answer instanceof ItsLastRun ? $ran($this->answer) : $held($this->answer);
    }
}
