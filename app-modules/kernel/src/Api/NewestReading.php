<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The newest reading a store keeps for a stack, or that it keeps none.
 *
 * What every store of readings answers when asked for one stack's, whichever
 * capability owns it: the reading is the owner's, and the three answers are
 * the same for each.
 *
 * **Three answers, and the third is the one a store cannot decide about.** A
 * row written in a shape this build has no name for — by a later version of
 * the app, before this one was put back — is still a row, and so is one whose
 * columns do not hold what this build writes into them. What to do with either
 * is the owner's decision, so the store says that it found one it cannot read
 * rather than dropping it or passing it off as nothing.
 *
 * Found carries the payload still sealed: a store never holds anything it
 * could read. One field holds either answer, so no state holds a reading and a
 * reason for not having one.
 */
final readonly class NewestReading
{
    private function __construct(private SealedReading|WhyNoReadingIsFound $answer) {}

    /** A reading, in a shape this build names, read at a moment. */
    public static function found(SealedPayload $payload, Shape $shape, Instant $readAt): self
    {
        return new self(SealedReading::of($payload, $shape, $readAt));
    }

    /** Nothing is kept for the stack. */
    public static function none(): self
    {
        return new self(WhyNoReadingIsFound::NoneIsKept);
    }

    /** A reading is kept, in a shape this build has no name for or in columns it did not write. */
    public static function thatThisBuildCannotRead(): self
    {
        return new self(WhyNoReadingIsFound::ItCannotBeRead);
    }

    /**
     * Whether the store holds a row for the stack, readable or not.
     *
     * A row this build cannot read is still a row, so a removal that asks
     * this waits for it to be let go of rather than taking the stack as gone.
     */
    public function holdsARow(): bool
    {
        return $this->answer !== WhyNoReadingIsFound::NoneIsKept;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TNone of object
     * @template TUnreadable of object
     *
     * @param Closure(SealedPayload, Shape, Instant): TFound $found
     * @param Closure(): TNone                               $none
     * @param Closure(): TUnreadable                         $unreadable
     *
     * @return TFound|TNone|TUnreadable
     */
    public function either(Closure $found, Closure $none, Closure $unreadable): object
    {
        if ($this->answer instanceof SealedReading) {
            return $found($this->answer->payload(), $this->answer->shape(), $this->answer->readAt());
        }

        return match ($this->answer) {
            WhyNoReadingIsFound::NoneIsKept => $none(),
            WhyNoReadingIsFound::ItCannotBeRead => $unreadable(),
        };
    }
}
