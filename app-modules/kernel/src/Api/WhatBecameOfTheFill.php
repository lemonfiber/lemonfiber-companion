<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What choosing a filler came to, or the reason it could not be learned.
 *
 * The answer {@see ChoosingAFiller} gives, and a value rather than an
 * exception for {@see WhatTheCatalogueSaid}'s reason. A choice worked out or
 * made comes back through the first arm and says which; one the stack turned
 * down for a reason it names comes back through the second; any other
 * refusal in the stack's words through the third; and what stood in the way
 * through the fourth.
 */
final readonly class WhatBecameOfTheFill
{
    private function __construct(private AFill|AFillTurnedDown|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack worked the choice out, and made it where it was agreed to. */
    public static function fill(AFill $fill): self
    {
        return new self($fill);
    }

    /** The stack turned the choice down, for a reason it names. */
    public static function turnedDown(AFillTurnedDown $why): self
    {
        return new self($why);
    }

    /** The stack refused for a reason of its own, in its words. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** It did not answer, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFill of object
     * @template TTurnedDown of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(AFill): TFill                 $fill
     * @param Closure(AFillTurnedDown): TTurnedDown $turnedDown
     * @param Closure(ARefusalInItsWords): TRefused $refused
     * @param Closure(Obstacle): TMet               $met
     *
     * @return TFill|TTurnedDown|TRefused|TMet
     */
    public function either(Closure $fill, Closure $turnedDown, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            $this->answer instanceof AFillTurnedDown => $turnedDown($this->answer),
            default => $fill($this->answer),
        };
    }
}
