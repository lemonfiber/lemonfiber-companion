<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `handoff` envelope that no other envelope this module reads carries.
 */
enum HandoffField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** How the person signs in on the new device, one step at a time. */
    case Steps = 'steps';

    /** Every app a device can be pointed at the stack with, and the code that points it. */
    case Clients = 'clients';

    /** Whether a client's code is a link that opens the app at this server, rather than the address alone. */
    case DeepLink = 'deep_link';

    /** Every device the media server lists as signed in to the account now. */
    case Sessions = 'sessions';

    /** When the media server last heard from a device signed in. */
    case LastSeen = 'last_seen';

    /** When the code was first given. */
    case Issued = 'issued';
}
