<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `adoption` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum AdoptionField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The services whose databases a newer version would upgrade. */
    case Upgrades = 'upgrades';

    /** The version of a service standing here now. */
    case Existing = 'existing';

    /** The host paths those services keep their data in. */
    case BackUp = 'back_up';

    /** Where the copy of those paths was written, once one has been taken. */
    case BackedUp = 'backed_up';
}
