<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a notification handed to the platform.
 *
 * A sum type rather than a boolean, for the reason every other one here is:
 * `true` and `false` are the same shape, so the call site that inverts them
 * compiles. Here the two arms take different arguments — the refusal carries
 * {@see WhyNothingIsShown} and the delivery carries nothing — so there is no
 * way to read one as the other.
 *
 * There is deliberately no `wasShown()` beside {@see self::either()}. A
 * check-then-get pair is an invitation to call the getter without the check,
 * and the whole reason a refusal is a value here is that somebody has to look
 * at the reason.
 */
final readonly class Shown
{
    private function __construct(private ?WhyNothingIsShown $withheld) {}

    /** The platform took it. */
    public static function delivered(): self
    {
        return new self(null);
    }

    /** It was not shown, and this is why. */
    public static function withheld(WhyNothingIsShown $because): self
    {
        return new self($because);
    }

    /**
     * @template TDelivered of object
     * @template TWithheld of object
     *
     * @param  Closure(): TDelivered  $delivered
     * @param  Closure(WhyNothingIsShown): TWithheld  $withheld
     * @return TDelivered|TWithheld
     */
    public function either(Closure $delivered, Closure $withheld): object
    {
        return $this->withheld instanceof WhyNothingIsShown
            ? $withheld($this->withheld)
            : $delivered();
    }
}
