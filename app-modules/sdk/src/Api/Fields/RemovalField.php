<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `removal` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum RemovalField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** How far taking somebody out got: everywhere, the media server only, or nothing. */
    case Revoked = 'revoked';

    /** Whether the request service holds an account for them at all. */
    case AsksThroughTheRequestService = 'asks-through-the-request-service';
}
