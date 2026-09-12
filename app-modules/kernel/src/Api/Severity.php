<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How much a problem matters.
 *
 * Four levels, deliberately. More would not be applied consistently, and
 * inconsistent severity is worse than coarse severity — a screen that sorts by
 * it is only as useful as the discipline behind the values.
 *
 * The four are the server's own, named in `contract/web-api.contract.json`.
 * They are written out here rather than derived from it because this module may
 * not read a file and may not know the SDK exists; what keeps them in step is
 * the adapter that maps the wire into them, which cannot compile against a case
 * that is missing.
 */
enum Severity: string
{
    /** Informational; nothing is required. */
    case Advisory = 'advisory';

    /** Degraded or risky, still working. */
    case Warning = 'warning';

    /** Something is broken. */
    case Error = 'error';

    /** Consequences outside the machine, or data at risk. */
    case Critical = 'critical';

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
