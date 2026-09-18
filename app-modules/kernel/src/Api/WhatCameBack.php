<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A stack's report, or the reason there is none.
 *
 * The answer {@see Asking} gives, and a value rather than an exception for the
 * reason `C1` states and {@see Admitted} demonstrates: a stack that is asleep,
 * one on another network, and one whose session has ended are ordinary states
 * of the world. A method answering with a {@see Report} could report them only
 * by throwing, which makes the common case the one nothing checks.
 *
 * **The reason is an {@see Obstacle}**, which is the set already drawn
 * and the set every other screen in this application reads. A second vocabulary
 * for *why you cannot see your stack right now* would be a second set of
 * sentences to write, translate and keep in step — and the operator meets the
 * same six situations whether they were signing in or asking after the machine.
 *
 * **There is no "asked and got nothing" case.** A stack that answered with a
 * report containing no findings is `Overall::Healthy` and an empty
 * {@see Findings}, which is the outcome the product exists to produce rather
 * than an absence — {@see Report} says so at more length.
 */
final readonly class WhatCameBack
{
    private function __construct(private Report|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function report(Report $report): self
    {
        return new self($report);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * A union field rather than two nullables, so there is no fourth state to
     * write an unreachable branch for — the shape {@see Admitted} settled on
     * after the mutation gate found the branch nothing could kill.
     *
     * @template TSaid of object
     * @template TMet of object
     *
     * @param Closure(Report): TSaid $said
     * @param Closure(Obstacle): TMet $met
     *
     * @return TSaid|TMet
     */
    public function either(Closure $said, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $said($this->answer);
    }
}
