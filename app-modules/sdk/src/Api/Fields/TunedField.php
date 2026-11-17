<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `tuned` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum TunedField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Where the tuner's own stream is served, carrying its pass. */
    case Direct = 'direct';

    /** Where a transcoded playlist of it is served, carrying its pass. */
    case Transcoded = 'transcoded';
}
