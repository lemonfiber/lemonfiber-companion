<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What running a recipe came to: whether every step held, why not where one did not, and each step.
 */
final readonly class WhatARecipeRunDid
{
    private function __construct(private bool $held, private string $why, private TheStepsThatRan $steps) {}

    /** As the stack reported it; `why` is empty where it gives none. */
    public static function reported(bool $held, string $why, TheStepsThatRan $steps): self
    {
        return new self($held, trim($why), $steps);
    }

    /** Whether every step it made came to what it should. */
    public function held(): bool
    {
        return $this->held;
    }

    /** Why it did not hold, in the stack's words; empty where it held or the stack says nothing. */
    public function why(): string
    {
        return $this->why;
    }

    /** Every step, in the recipe's order, with what each came to. */
    public function steps(): TheStepsThatRan
    {
        return $this->steps;
    }
}
