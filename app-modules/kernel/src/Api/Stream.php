<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which of a service's two mouths a line came out of.
 *
 * An enum, because the set is closed by the contract rather than by this app —
 * `log.stream` is exactly these two, so `D4` gets its enum and a third would be
 * a contract change rather than a value to pass through.
 *
 * **Worth carrying rather than flattening.** A service writes its ordinary
 * running commentary to one and its complaints to the other, and an operator
 * scanning two hundred lines for the moment something went wrong is looking for
 * the second. Dropping the distinction makes the screen a wall of text where
 * the one line worth reading looks like the other hundred and ninety-nine.
 *
 * **It is not a severity.** Plenty of well-behaved services write ordinary
 * progress to `stderr`, so a screen treating this as *error* would put a red
 * mark against a service that is working — which is `Severity`'s job and comes
 * from a check, not from a file descriptor.
 */
enum Stream: string
{
    /** The ordinary running commentary. */
    case Stdout = 'stdout';

    /** Where a service writes what it wants noticed — not necessarily a fault. */
    case Stderr = 'stderr';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. Nested under `health.stream.` beside the groups already there.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.stream.%s', $this->value);
    }

    /**
     * Whether a screen should let this line stand out from the rest.
     *
     * The one decision that belongs here rather than on a screen, and it is
     * deliberately weaker than it looks: *worth noticing* is not *wrong*. A
     * screen wanting a red mark wants {@see Severity}, which comes from a check
     * that decided something, and this comes from which file descriptor a
     * process happened to write to.
     */
    public function worthNoticing(): bool
    {
        return $this === self::Stderr;
    }
}
