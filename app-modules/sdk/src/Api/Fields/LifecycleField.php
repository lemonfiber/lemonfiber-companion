<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `lifecycle` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum LifecycleField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What the forms named came to: what would run, and what the configuration left out. */
    case Plan = 'plan';

    /** Host ports the verb wanted that another project on the machine already answers on. */
    case PortConflicts = 'port_conflicts';
}
