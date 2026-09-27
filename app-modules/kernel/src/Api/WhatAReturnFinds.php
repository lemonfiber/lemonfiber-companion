<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a screen opened again finds left running: a handle to follow, or nothing.
 *
 * Every answer {@see WorkLeftRunning} gives is this, because each is the same
 * question asked at a different moment. Read, it is what the device holds.
 * Kept, it is what a return will find: the handle where the device wrote it
 * down, nothing where it would not. Let go of, it is nothing.
 *
 * **No reason rides on the empty arm.** A store that will not open, a device
 * with none, a value this build did not write and a handle never kept all come
 * to the same thing for the screen asking: there is no work to pick up from
 * here, and the stack carries on with it regardless. A reason nobody can act
 * on differently is a value no screen reads, which {@see Noted} says of the
 * same store.
 */
final readonly class WhatAReturnFinds
{
    private function __construct(private ?Job $job) {}

    /** The handle of the work left running, to ask the stack after. */
    public static function theJob(Job $job): self
    {
        return new self($job);
    }

    /** Nothing to pick up: a return offers to start the work rather than follow it. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /**
     * Say what happens for a handle and for none, and get back what you built.
     *
     * No `job()` and no `isEmpty()`, for `Outcome`'s reason: a check-then-get
     * pair puts the check where it can be forgotten, and the forgotten one here
     * asks a stack after a handle that is not there.
     *
     * @template TJob of object
     * @template TNothing of object
     *
     * @param Closure(Job): TJob   $job
     * @param Closure(): TNothing  $nothing
     *
     * @return TJob|TNothing
     */
    public function either(Closure $job, Closure $nothing): object
    {
        return $this->job instanceof Job ? $job($this->job) : $nothing();
    }
}
