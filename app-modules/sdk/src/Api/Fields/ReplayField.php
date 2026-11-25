<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `replay` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ReplayField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether every alert raised since the id the stream was opened after follows. */
    case Alerts = 'alerts';
}
