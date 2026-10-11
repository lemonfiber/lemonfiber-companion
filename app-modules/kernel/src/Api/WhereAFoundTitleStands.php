<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a title a member found stands in the house, as the core answered it.
 *
 * Only `Askable` may be asked for. The others are said, never offered as an
 * ask: a title already here or on its way is not asked for twice.
 */
enum WhereAFoundTitleStands: string
{
    /** The house holds all of it. */
    case Here = 'here';

    /** The house holds some of it. */
    case PartlyHere = 'partly-here';

    /** Somebody asked for it and it is on its way. */
    case OnItsWay = 'on-its-way';

    /** Nobody has, and the member may ask for it. */
    case Askable = 'askable';

    /** Whether a member may ask for a title standing here. */
    public function mayBeAskedFor(): bool
    {
        return $this === self::Askable;
    }
}
