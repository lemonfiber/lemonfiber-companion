<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a run was a rehearsal, which changed nothing, or the thing itself.
 *
 * Carried on the answer and never inferred, because a rehearsal read as the
 * real thing is the report of a copy that does not exist.
 */
enum WhetherItWasRehearsed: string
{
    /** It was run to find out what would happen, and nothing was written. */
    case Rehearsed = 'rehearsed';

    /** It happened. */
    case CarriedOut = 'carried_out';

    /** Read off the wire's boolean, which is the only place one is taken. */
    public static function said(bool $rehearsed): self
    {
        return $rehearsed ? self::Rehearsed : self::CarriedOut;
    }
}
