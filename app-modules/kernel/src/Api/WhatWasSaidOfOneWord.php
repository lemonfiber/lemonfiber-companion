<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack said when asked for one word, or the reason it could not be asked.
 *
 * The answer {@see Explaining::wordOn()} gives. Three arms, because a stack
 * with no entry for the word is an answer rather than a failure: the word is
 * then shown as it came, and asking again would say the same thing.
 */
final readonly class WhatWasSaidOfOneWord
{
    private function __construct(
        private ?AWord $word = null,
        private ?Obstacle $met = null,
    ) {}

    /** The stack explained it, and this is its entry. */
    public static function explained(AWord $word): self
    {
        return new self(word: $word);
    }

    /** The stack has no entry for it either. */
    public static function unexplained(): self
    {
        return new self();
    }

    /** It could not be asked, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Every arm required, for {@see WhatWasFoundOfTheWords::either()}'s reason:
     * a default is where a stack that could not be asked reads as a word it
     * does not explain.
     *
     * @template T of object
     *
     * @param Closure(AWord): T    $explained
     * @param Closure(): T         $unexplained
     * @param Closure(Obstacle): T $met
     *
     * @return T
     */
    public function either(Closure $explained, Closure $unexplained, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->word instanceof AWord => $explained($this->word),
            default => $unexplained(),
        };
    }
}
