<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `replacement` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ReplacementField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The services that would be stopped. */
    case WouldStop = 'would_stop';

    /** The services that would not stop and are still up. */
    case StillRunning = 'still_running';
}
