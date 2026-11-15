<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `push-endpoint` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum PushEndpointField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The address the stack sends a push to, as it keeps it. */
    case Endpoint = 'endpoint';
}
