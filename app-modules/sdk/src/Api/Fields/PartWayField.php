<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `part-way` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum PartWayField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What the member was part-way through, most recent first. */
    case PartWay = 'part_way';

    /** How long it runs, in whole seconds, where the server knows. */
    case Length = 'length';
}
