<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What became of something this application asked the notification centre to do.
 *
 * A sum type rather than a boolean, for the reason the wire answers a word
 * rather than a bool: `true` and `false` are the same shape, so the call site
 * that inverts them compiles. Here the two arms take different arguments — the
 * refusal carries a {@see WhyNothingWasTold} and the success carries nothing —
 * so there is no way to read one as the other.
 *
 * There is deliberately no `wasTold()` beside {@see self::either()}. A
 * check-then-get pair is an invitation to call the getter without the check,
 * and the whole reason a refusal is a value here is that somebody has to look
 * at the reason.
 */
final readonly class Told
{
    private function __construct(private ?WhyNothingWasTold $withheld) {}

    /** The platform took it. */
    public static function done(): self
    {
        return new self(null);
    }

    /** It did not, and this is why. */
    public static function withheld(WhyNothingWasTold $because): self
    {
        return new self($because);
    }

    /**
     * @template TDone of object
     * @template TWithheld of object
     *
     * @param  Closure(): TDone  $done
     * @param  Closure(WhyNothingWasTold): TWithheld  $withheld
     * @return TDone|TWithheld
     */
    public function either(Closure $done, Closure $withheld): object
    {
        return $this->withheld instanceof WhyNothingWasTold
            ? $withheld($this->withheld)
            : $done();
    }
}
