<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;

/**
 * A length of time the stack counted, in seconds, and how it is said.
 *
 * What the stack sends as a count of seconds, such as how long a stopped item
 * has been that way. Said in {@see HowLongAgo}'s bands, the ones an age is
 * said in, so *stuck for three hours* and *heard three hours ago* agree about
 * where an hour starts.
 *
 * **The count comes from the unit**, for {@see HowLongAgo}'s reason: the number
 * is the span divided by the unit's own length, so the two cannot disagree.
 */
final readonly class HowLong
{
    private function __construct(private int $seconds) {}

    /**
     * A span as the stack counted it.
     *
     * Less than no time is refused: it is a count the stack cannot have meant,
     * and said on a screen it would be a sentence nobody could believe.
     */
    public static function ofSeconds(int $seconds): self
    {
        if ($seconds < 0) {
            throw HowLongIsBelowNothing::seconds($seconds);
        }

        return new self($seconds);
    }

    /** The unit it is said in: the coarsest it fills at least one of. */
    public function unit(): HowLongAgo
    {
        return HowLongAgo::over($this->seconds);
    }

    /** How many of that unit it holds, floored. */
    public function howMany(): int
    {
        return intdiv($this->seconds, $this->unit()->seconds());
    }

    /** The seconds as the stack counted them. */
    public function inSeconds(): int
    {
        return $this->seconds;
    }
}
