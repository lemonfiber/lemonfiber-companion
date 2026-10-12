<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `pausing` envelope that no other envelope this module reads carries.
 *
 * The rest — `asked`, `clients`, `client`, `was`, `now`, `offer`, `caution` and `rehearsed` — are
 * shared, and named once in {@see \Modules\Sdk\Api\WireField}.
 */
enum PausingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Why a client could not be reached, in its own words where it gave any. */
    case Unreached = 'unreached';
}
