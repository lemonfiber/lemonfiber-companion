<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The word a stack answers where removing everything lemonfiber keeps got to on one run.
 *
 * The wire's three tags, each deciding which of {@see WhereForgettingGot}'s constructors a reading answers with.
 */
enum WhereTheForgettingStands: string
{
    /** Only the listing was asked for. */
    case NotAsked = 'not-asked';

    /** Removing it was asked about without the yes, and nothing went. */
    case Unconfirmed = 'unconfirmed';

    /** It was removed. */
    case Done = 'done';
}
