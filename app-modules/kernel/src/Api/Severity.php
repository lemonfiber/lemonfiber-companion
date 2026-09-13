<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function sprintf;

/**
 * How much a problem matters.
 *
 * Four levels, deliberately. More would not be applied consistently, and
 * inconsistent severity is worse than coarse severity — a screen that sorts by
 * it is only as useful as the discipline behind the values.
 *
 * **Declared worst first**, which is what {@see self::isWorseThan()} reads and
 * what {@see \Modules\Health\Api\Queries\WorstFirst} breaks ties with. The
 * order is the meaning, so moving a case is a failing test rather than a screen
 * that quietly reorders itself — the argument {@see Conclusion} makes about its
 * own cases, and for the same reason.
 *
 * The four are the server's own, named in `contract/web-api.contract.json`.
 * They are written out here rather than derived from it because this module may
 * not read a file and may not know the SDK exists; what keeps them in step is
 * the adapter that maps the wire into them, which cannot compile against a case
 * that is missing.
 */
enum Severity: string
{
    /** Consequences outside the machine, or data at risk. */
    case Critical = 'critical';

    /** Something is broken. */
    case Error = 'error';

    /** Degraded or risky, still working. */
    case Warning = 'warning';

    /** Informational; nothing is required. */
    case Advisory = 'advisory';

    /**
     * Whether this matters more than another, by the order the cases are in.
     *
     * Written exactly as {@see Conclusion::isWorseThan()} is, and deliberately
     * not as a comparison of values: the words do not sort alphabetically into
     * their own meaning, and a numbered case would be a second declaration of
     * an order the enum already makes.
     */
    public function isWorseThan(self $other): bool
    {
        $first = array_find(
            self::cases(),
            fn(self $case): bool => $case === $this || $case === $other,
        );

        return $first === $this && $this !== $other;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. Under `health.severity.` beside the groups that already exist.
     *
     * Shown beside the verdict rather than instead of it. They answer different
     * questions: the verdict is whether the check passed and this is how much
     * the answer costs, and two failed checks where one puts data at risk must
     * not read the same.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.severity.%s', $this->value);
    }

    /**
     * Whether this is something the operator has to be shown now.
     *
     * The line is drawn once, here, rather than at each screen that needs it:
     * a screen deciding for itself is how two screens come to disagree about
     * what counts as urgent, and the operator learns that one of them is lying.
     */
    public function demandsAttention(): bool
    {
        return match ($this) {
            self::Advisory, self::Warning => false,
            self::Error, self::Critical => true,
        };
    }
}
