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

    /** The alert preset in force for events with no exception of their own. */
    case Preset = 'preset';

    /** The events set apart from an alert preset. */
    case Exceptions = 'exceptions';

    /**
     * The kind of event set apart, by the name a finding gives it.
     *
     * A row's field, not the envelope's `kind`, which the transport reads
     * before this app ever sees a payload.
     */
    case Kind = 'kind';
}
