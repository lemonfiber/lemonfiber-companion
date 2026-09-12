<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How long the app waits for a stack before it gives the screen back.
 *
 * `N1-R26` has two clauses and the second is the one that needs a type. Every
 * call carries a bound — that is a habit, and habits hold. The bound must not be
 * raised to accommodate a slow stack — that is a decision, it arrives at three
 * in the morning while somebody is debugging a stack that takes eleven seconds
 * to answer, and raising the number makes the symptom go away.
 *
 * **It is the wrong cure, and the reason is what the requirement is protecting.**
 * A timeout is not a budget for how long a stack may take. It is how long an
 * operator holds a phone that is doing nothing, and that number is about the
 * person rather than the machine. A stack that needs twelve seconds is a stack
 * with something wrong with it, and the app's job is to say so — `Obstacle` and
 * `Reach` exist for exactly that — rather than to wait quietly for longer.
 *
 * So {@see self::CEILING} is not a default. It is a maximum, `of()` refuses
 * anything above it, and a test pins the value so that raising it is an edit
 * that fails a test naming the requirement rather than a number somebody
 * changes while looking at something else.
 *
 * Seconds rather than milliseconds, because every number in this class is one a
 * person can feel and nothing here needs finer than that. An adapter that talks
 * to a client wanting milliseconds converts at the edge, which is where the unit
 * mismatch is visible.
 */
final readonly class Timeout
{
    /**
     * The longest any call may wait, in seconds.
     *
     * Ten, and the number is arguable — what is not arguable is that it is a
     * ceiling rather than a suggestion. `ADR-0019` has a screen painting before
     * it reaches the stack, so the operator is already looking at something
     * while this runs; ten seconds of a spinner over a frame that already says
     * something is the far end of what a person reads as "working" rather than
     * as "broken".
     */
    public const int CEILING = 10;

    /** The shortest, in seconds. Below this a healthy stack on a busy network loses. */
    public const int FLOOR = 1;

    private function __construct(private int $seconds) {}

    public static function of(int $seconds): self
    {
        if ($seconds > self::CEILING) {
            throw ReachWaitsTooLong::forTheOperator($seconds);
        }

        if ($seconds < self::FLOOR) {
            throw ReachWaitsTooLong::forTheStack($seconds);
        }

        return new self($seconds);
    }

    /** The bound every call carries unless it has a reason not to. */
    public static function ordinary(): self
    {
        return new self(self::CEILING);
    }

    /** What a client is given, in the unit this class speaks. */
    public function inSeconds(): int
    {
        return $this->seconds;
    }

    public function is(self $other): bool
    {
        return $this->seconds === $other->seconds;
    }
}
