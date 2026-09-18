<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of trying to write down a stack: it is remembered, or it is not.
 *
 * A device holds more than one configured stack, and a pairing this device
 * cannot write down is a pairing that did not happen — the operator scanned a
 * code, watched something succeed, and will find nothing there next launch. So
 * the refusal is a value a caller has to look at rather than an exception it
 * can forget, which is the argument {@see Kept} makes about a session and `C1`
 * makes generally.
 *
 *     $remembered->either(
 *         remembered: fn (): Screen => $this->showTheStack(),
 *         refused: fn (WhyAStackCannotBeRemembered $why): Screen => $this->explain($why),
 *     );
 *
 * Its own type rather than `Kept`, whose name and whose reason enum are both
 * about a session. Two outcomes that read the same and mean different things
 * are the shape this codebase keeps apart on purpose: one of them is a sign-in
 * that will not outlive the app, and this one is a machine the operator thinks
 * they paired.
 */
final readonly class Remembered
{
    private function __construct(private ?WhyAStackCannotBeRemembered $why) {}

    /** The stack is written down where a later launch will find it. */
    public static function safely(): self
    {
        return new self(null);
    }

    /** It is not, and this is which nothing stopped it. */
    public static function refused(WhyAStackCannotBeRemembered $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `wasRemembered()` and no `why()`, for the reason `Outcome`
     * gives: a check-then-get pair puts the check where it can be forgotten,
     * and the forgotten one here is an app showing a stack it has not kept.
     *
     * @template TRemembered of object
     * @template TRefused of object
     *
     * @param Closure(): TRemembered                             $remembered
     * @param Closure(WhyAStackCannotBeRemembered): TRefused     $refused
     *
     * @return TRemembered|TRefused
     */
    public function either(Closure $remembered, Closure $refused): object
    {
        if ($this->why instanceof WhyAStackCannotBeRemembered) {
            return $refused($this->why);
        }

        return $remembered();
    }
}
