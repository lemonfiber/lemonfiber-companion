<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One act as a healing run took it: the act, what became of it, what was done in its place and why.
 */
final readonly class AnActTaken
{
    private function __construct(private AHealingAct $act, private WhatBecameOfAnAct $outcome, private string $why, private ?AHealingAct $instead) {}

    /** This act, what became of it, and why where it was refused or fell back, in the stack's words, or empty. */
    public static function took(AHealingAct $act, WhatBecameOfAnAct $outcome, string $why): self
    {
        return new self($act, $outcome, $why, null);
    }

    /** The same act, with something weaker done in its place. */
    public function inPlaceOf(AHealingAct $instead): self
    {
        return new self($this->act, $this->outcome, $this->why, $instead);
    }

    /** The act. */
    public function act(): AHealingAct
    {
        return $this->act;
    }

    /** What became of it. */
    public function outcome(): WhatBecameOfAnAct
    {
        return $this->outcome;
    }

    /** Why it was refused or fell back, empty where nothing says. */
    public function why(): string
    {
        return $this->why;
    }

    /**
     * What was done in its place, or that nothing was.
     *
     * @template TInstead of object
     * @template TNothing of object
     *
     * @param  Closure(AHealingAct): TInstead $instead
     * @param  Closure(): TNothing            $nothing
     * @return TInstead|TNothing
     */
    public function insteadOf(Closure $instead, Closure $nothing): object
    {
        return $this->instead instanceof AHealingAct ? $instead($this->instead) : $nothing();
    }
}
