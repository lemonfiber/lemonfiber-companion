<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `grant` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum GrantField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The token the device presents at the door. */
    case Token = 'token';

    /** The last day the grant holds, as `YYYY-MM-DD`. */
    case LastsUntil = 'lasts_until';
}
