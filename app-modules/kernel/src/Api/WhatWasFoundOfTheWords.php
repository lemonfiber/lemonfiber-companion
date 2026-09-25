<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The glossary, or the reason it could not be read.
 *
 * The answer {@see Explaining} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 *
 * **An empty glossary comes back through the first arm.** It and a stack that
 * could not be asked would otherwise both draw an empty list, and only one of
 * them is an answer.
 */
final readonly class WhatWasFoundOfTheWords
{
    private function __construct(private TheGlossary|Obstacle $answer) {}

    /** The stack answered, and these are its words. */
    public static function found(TheGlossary $words): self
    {
        return new self($words);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required: an optional arm is a default, and a default is where
     * an obstacle quietly becomes an empty list on a screen.
     *
     * @template TAnswered of object
     * @template TMet of object
     *
     * @param Closure(TheGlossary): TAnswered $found
     * @param Closure(Obstacle): TMet $met
     *
     * @return TAnswered|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
