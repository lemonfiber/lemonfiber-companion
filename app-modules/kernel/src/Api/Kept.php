<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of trying to keep a session: it was kept, or there was nowhere safe.
 *
 * `N4-R6` requires the app to refuse **and say why**, and this is both in one
 * value. It started as an exception and `C1` was right to refuse that: a method
 * that answers with nothing can only report a refusal by throwing, which makes
 * the common case the one nothing checks. A device with no secure storage is an
 * ordinary state of the world, not an exceptional one.
 *
 * Removing the exception settled a second thing too. `shipmonk` forbids throwing
 * a checked exception inside a closure, and every Pest test body is a closure —
 * so an `@throws` on the port made the refusal the one case no test could
 * exercise. A type has no such problem: the refusal is a value, and a value can
 * be asserted anywhere.
 *
 *     $kept->either(
 *         kept: fn (): Screen => $this->carryOn(),
 *         refused: fn (WhySessionCannotBeKept $why): Screen => $this->explain($why),
 *     );
 *
 * The shape is {@see Reach}'s, and it stays its own class for the reason
 * `Reading` gives: what makes any of them readable at a call site is the pair of
 * names, and a shared `left`/`right` would trade that away.
 */
final readonly class Kept
{
    private function __construct(private ?WhySessionCannotBeKept $why) {}

    /** The session is where `N4-R5` requires it. */
    public static function safely(): self
    {
        return new self(null);
    }

    /** There was nowhere safe, and this is which nowhere it was. */
    public static function refused(WhySessionCannotBeKept $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `wasKept()` and no `why()`, for the reason `Outcome` gives: a
     * check-then-get pair puts the check where it can be forgotten, and the
     * forgotten one here is an app that believes it has remembered a session it
     * did not.
     *
     * @template TKept of object
     * @template TRefused of object
     *
     * @param Closure(): TKept                          $kept
     * @param Closure(WhySessionCannotBeKept): TRefused $refused
     *
     * @return TKept|TRefused
     */
    public function either(Closure $kept, Closure $refused): object
    {
        // Read off the refusal rather than off success, so the failing branch
        // is the one the type is written around. The other way up makes a
        // refusal the fall-through, which is how it becomes the case nobody
        // tested.
        return $this->why instanceof WhySessionCannotBeKept
            ? $refused($this->why)
            : $kept();
    }
}
