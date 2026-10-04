<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How one connection turned out after a wiring run, in the stack's word for it.
 *
 * Thirteen answers, and they are the report: a run that turned them into one
 * tick would throw away the only thing an operator can act on. *Skipped* is a
 * prerequisite that was not there and a later run finishes, which is not
 * *failed*; *drifted* is the operator's own change, kept, which is neither
 * wired nor something to put back.
 */
enum WhereAConnectionStands: string
{
    /** Written and read back. */
    case Wired = 'wired';

    /** Present and correct; nothing was done. */
    case AlreadyWired = 'already-wired';

    /** Present and changed by the operator, and kept as they left it. */
    case Drifted = 'drifted';

    /** Still lemonfiber's own value and behind what it would write now; left as it is. */
    case Stale = 'stale';

    /** The operator's value and lemonfiber's both moved; shown side by side and left as it is. */
    case Conflicted = 'conflicted';

    /** An operator's edit taken on as the accepted state. */
    case Adopted = 'adopted';

    /** A value lemonfiber never wrote, taken on as it was. */
    case Unmanaged = 'unmanaged';

    /** What a run that wrote would have written, where this one only said so. */
    case WouldWire = 'would-wire';

    /** An operator's value a run that wrote would have taken on, where this one only said so. */
    case WouldAdopt = 'would-adopt';

    /** An area the operator declared unmanaged; nothing was read or written. */
    case Observed = 'observed';

    /** Something fills what a service asks for, and nothing lemonfiber does connects the two; no run changes it. */
    case Unmatched = 'unmatched';

    /** A prerequisite was not there; a later run finishes it. */
    case Skipped = 'skipped';

    /** Attempted, and the service rejected it in its own words. */
    case Failed = 'failed';

    /** Refused by lemonfiber's own policy, for a reason a later run does not resolve. */
    case Refused = 'refused';
}
