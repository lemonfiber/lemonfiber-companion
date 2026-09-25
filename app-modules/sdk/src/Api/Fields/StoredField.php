<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `stored` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum StoredField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The directories everything a stack keeps sits under. */
    case Roots = 'roots';

    /** Each thing a stack keeps on its machine. */
    case Kept = 'kept';
}
