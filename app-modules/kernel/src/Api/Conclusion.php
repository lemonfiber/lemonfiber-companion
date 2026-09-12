<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;

/**
 * How a check turned out, as one word, worst first.
 *
 * `Unverified` is its own case rather than a level of severity, which is the
 * dishonesty this whole subsystem exists to prevent: a check that could not
 * run must never be readable as one that passed. It is ranked above `Warned`
 * for the same reason — not knowing whether the tunnel leaks is worse than
 * knowing the disk is filling.
 *
 * **The declaration order is the ranking.** It is not a number attached to
 * each case, because a number is a second thing to keep in step and the order
 * is already right there in the file. `ConclusionTest` pins the order, so
 * moving a case is a failing test rather than a screen that quietly reorders
 * itself.
 */
enum Conclusion: string
{
    /** Not working. */
    case Failed = 'fail';

    /** Could not be established. Never a pass. */
    case Unverified = 'unverified';

    /** Working, but degraded or risky. */
    case Warned = 'warn';

    /** Verified working. */
    case Passed = 'pass';

    /** A prerequisite was absent, so the check did not apply. */
    case Skipped = 'skipped';

    /**
     * Whether this belongs above another on the screen.
     *
     * Of the two, whichever is declared first: if that is this one and they
     * are not the same case, this one is worse. Written as one expression
     * rather than as a rank per case, because a rank is a second statement of
     * the order and the order is already in the file — and because a lookup
     * that can miss needs a branch for a case that cannot happen, which is a
     * line no test can reach and no mutant can be killed on.
     */
    public function isWorseThan(self $other): bool
    {
        $first = array_find(
            self::cases(),
            fn(self $case): bool => $case === $this || $case === $other,
        );

        return $first === $this && $this !== $other;
    }
}
