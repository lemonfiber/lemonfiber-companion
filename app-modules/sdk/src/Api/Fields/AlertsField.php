<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `alerts` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum AlertsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The events set apart from an alert preset. */
    case Exceptions = 'exceptions';

    /** The kinds of notification the stack raises as it is configured. */
    case Kinds = 'kinds';
}
