<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use Closure;

/**
 * What became of asking the device to put the player on screen.
 *
 * A sum type rather than a boolean for the reason {@see \Lemonfiber\Native\Wrote}
 * is one: the refusal carries why, and the caller that has to say something
 * about it cannot read it as the other arm.
 */
final readonly class Opening
{
    private function __construct(private ?WhyThePlayerDidNotOpen $why) {}

    /** The player is on screen. */
    public static function opened(): self
    {
        return new self(why: null);
    }

    /** It is not, and this is why. */
    public static function refused(WhyThePlayerDidNotOpen $why): self
    {
        return new self(why: $why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TOpened of object
     * @template TRefused of object
     *
     * @param  Closure(): TOpened  $opened
     * @param  Closure(WhyThePlayerDidNotOpen): TRefused  $refused
     * @return TOpened|TRefused
     */
    public function either(Closure $opened, Closure $refused): object
    {
        if ($this->why instanceof WhyThePlayerDidNotOpen) {
            return $refused($this->why);
        }

        return $opened();
    }
}
