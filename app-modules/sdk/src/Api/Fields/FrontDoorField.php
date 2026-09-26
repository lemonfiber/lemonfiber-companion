<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `front-door` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum FrontDoorField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The whole address, as it would be typed or followed. */
    case Url = 'url';

    /** What a service is to the household. */
    case Facing = 'facing';

    /**
     * How the door came to be the one it is — the tag of that union, and the
     * field that carries it.
     */
    case Chosen = 'chosen';

    /** What the operator named as the door: an id where it stands, a table where it was refused. */
    case Door = 'door';
}
