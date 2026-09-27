<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `import` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ImportField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What was carried across. */
    case Carried = 'carried';

    /** What would be, where nothing has been yet. */
    case WouldCarry = 'would_carry';
}
