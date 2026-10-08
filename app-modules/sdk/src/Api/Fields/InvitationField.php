<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `invitation` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum InvitationField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** How many hours an invitation stands before it is withdrawn. */
    case Hours = 'hours';

    /** Whether the request service knows about the household yet. */
    case Linked = 'linked';

    /** What a limit is and what it is not, in the stack's words. */
    case Filtering = 'filtering';

    /** The libraries an invited member may open. */
    case Libraries = 'libraries';

    /** Whether the request service was held to the same decision. */
    case Requesting = 'requesting';

    /** What becomes of material with no rating: `block` or `allow`. */
    case Unrated = 'unrated';

    /** Resets that lapsed, switched off on the way past and kept, by account name. */
    case Suspended = 'suspended';

    /** The address that turns the invitation down, where the stack gave one. */
    case Decline = 'decline';
}
