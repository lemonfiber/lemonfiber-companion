<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `boot` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum BootField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** When the machine last started. */
    case BootedAt = 'booted_at';

    /** The newest check kept against any start. */
    case LastVerified = 'last_verified';

    /** Why a run after the latest start started nothing. */
    case Declined = 'declined';

    /** How many starts the stack has failed to come back after. */
    case FailedBoots = 'failed_boots';

    /** Since when the stack has failed to come back. */
    case FailingSince = 'failing_since';

    /** Each service that did not come back. */
    case NotBack = 'not_back';

    /** Whether the tunnel came back with the stack. */
    case Tunnel = 'tunnel';

    /** Whether every client takes the forwarded port. */
    case Pushed = 'pushed';
}
