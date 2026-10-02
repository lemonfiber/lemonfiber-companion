<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack lists that could be new, or the reason it could not be read.
 *
 * The answer {@see ReadingNews} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 */
final readonly class WhatWasFoundOfTheNews
{
    private function __construct(private TheNewsOfAStack|Obstacle $answer) {}

    /** The stack answered, and this is what it listed. */
    public static function found(TheNewsOfAStack $news): self
    {
        return new self($news);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, for {@see WhatWasFoundOfItself::either()}'s reason.
     *
     * @template TFound of object
     * @template TMet of object
     *
     * @param Closure(TheNewsOfAStack): TFound $found
     * @param Closure(Obstacle): TMet $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
