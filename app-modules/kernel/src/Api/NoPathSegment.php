<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The names that are no path segment once a path is normalised: nothing, and the two dot segments.
 *
 * A stack is asked after a job, a key, a phone key and a support bundle by one
 * segment of a path that names it, and one of these would reach another route,
 * so each is refused where the name arrives.
 */
enum NoPathSegment: string
{
    /** No name at all. */
    case Nothing = '';

    /** The segment a normalised path drops. */
    case Here = '.';

    /** The segment a normalised path climbs out of the route with. */
    case Up = '..';
}
