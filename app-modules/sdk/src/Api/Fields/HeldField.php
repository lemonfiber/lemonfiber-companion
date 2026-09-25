<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `held` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum HeldField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What one member may watch, as the media server answered it. */
    case Holdings = 'holdings';

    /** What kind of thing one holding is. */
    case Medium = 'medium';

    /** When a holding came out. Absent where the core could not date it. */
    case Year = 'year';
}
