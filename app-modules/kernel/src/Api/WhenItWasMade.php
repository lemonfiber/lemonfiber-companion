<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * When a change was made, or the fact that the clock would not say.
 *
 * Two arms because the stack has two answers. It stamps a change with seconds
 * since the epoch, and where its clock would not answer it stamps zero — so
 * zero is not the first second of 1970 but *nobody could tell*, and a screen
 * that drew it as a date would be answering from a guess the stack refused to
 * make.
 *
 * **An unreadable clock is never the same moment as anything**, including
 * another unreadable clock. Two changes the stack could not date are two
 * changes nothing orders, and drawing them as one moment would claim they
 * happened together — which nobody knows either.
 */
final readonly class WhenItWasMade
{
    private function __construct(private ?Instant $at) {}

    /** It was made at this moment. */
    public static function at(Instant $at): self
    {
        return new self($at);
    }

    /** The stack's clock would not say when. */
    public static function unreadable(): self
    {
        return new self(null);
    }

    /**
     * Say what happens for a known moment and for an unreadable clock.
     *
     * Both arms required, which is `C2`'s reason: a nullable instant printed
     * as a date is the 1970 this type exists to keep off a screen.
     *
     * @template TAt of object
     * @template TUnreadable of object
     *
     * @param  Closure(Instant): TAt       $at
     * @param  Closure(): TUnreadable      $unreadable
     * @return TAt|TUnreadable
     */
    public function either(Closure $at, Closure $unreadable): object
    {
        return $this->at instanceof Instant ? $at($this->at) : $unreadable();
    }

    /**
     * Whether two changes were made at one known moment.
     *
     * What a record draws as one moment rather than as one change after
     * another. False wherever either clock was unreadable, for the reason the
     * class gives.
     */
    public function isTheSameMomentAs(self $other): bool
    {
        return $this->at instanceof Instant && $other->at instanceof Instant && $this->at->is($other->at);
    }
}
