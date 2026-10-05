<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack wires to what, or the reason that could not be learned.
 *
 * The answer {@see Linking} gives, and a value rather than an exception for
 * {@see WhatTheCatalogueSaid}'s reason. **A stack that asks nothing of its
 * services comes back through the first arm**, as an empty {@see TheLinks}. A
 * stack that answered and could not read its own wiring comes back through the
 * second, in its words, and is never drawn as settled.
 */
final readonly class WhatTheLinksSaid
{
    private function __construct(private TheLinks|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack answered, and this is what it wires to what. */
    public static function links(TheLinks $links): self
    {
        return new self($links);
    }

    /** The stack answered, and could not say, and this is why in its words. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TLinks of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(TheLinks): TLinks             $links
     * @param Closure(ARefusalInItsWords): TRefused $refused
     * @param Closure(Obstacle): TMet               $met
     *
     * @return TLinks|TRefused|TMet
     */
    public function either(Closure $links, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $links($this->answer),
        };
    }
}
