<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What became of asking the store to keep or forget something.
 *
 * A sum type rather than a boolean, for the reason the wire answers a word
 * rather than a bool: `true` and `false` are the same shape, so the call site
 * that inverts them compiles. Here the two arms take different arguments — the
 * refusal carries a {@see WhyNothingWasKept} and the success carries the moment
 * the value may be read again — so there is no way to read one as the other.
 *
 * There is deliberately no `wasKept()` beside {@see self::either()}. A
 * check-then-get pair is an invitation to call the getter without the check,
 * and the whole reason a refusal is a value here is that somebody has to look
 * at the reason and say a different sentence about each.
 */
final readonly class Wrote
{
    private function __construct(
        private ?WhenAValueMayBeRead $readable,
        private ?WhyNothingWasKept $why,
    ) {}

    /**
     * The store did it, and this is when the value may be read again.
     *
     * The moment comes back rather than being assumed from what was asked for,
     * because the platforms do not both honour the request. A caller that
     * needs the narrower one can see that it did not get it.
     */
    public static function done(WhenAValueMayBeRead $readable): self
    {
        return new self(readable: $readable, why: null);
    }

    /** It did not, and this is which of the two ways. */
    public static function refused(WhyNothingWasKept $why): self
    {
        return new self(readable: null, why: $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TDone of object
     * @template TRefused of object
     *
     * @param  Closure(WhenAValueMayBeRead): TDone  $done
     * @param  Closure(WhyNothingWasKept): TRefused  $refused
     * @return TDone|TRefused
     */
    public function either(Closure $done, Closure $refused): object
    {
        if ($this->why instanceof WhyNothingWasKept) {
            return $refused($this->why);
        }

        return $done($this->readable ?? WhenAValueMayBeRead::WhileUnlocked);
    }
}
