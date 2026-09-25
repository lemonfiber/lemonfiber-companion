<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `invitation` envelope, and each argument of the actions answering with one, that no other envelope this module reads carries.
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

    /** Whether the account was made, or only described. */
    case Rehearsed = 'rehearsed';

    /** What a limit is and what it is not, in the stack's words. */
    case Filtering = 'filtering';

    /** The libraries an invited member may open; on the way out, the ones asked for. */
    case Libraries = 'libraries';

    /** Whether the request service was held to the same decision. */
    case Requesting = 'requesting';

    /** What becomes of material with no rating; on the way out, `block` or `allow`. */
    case Unrated = 'unrated';

    /** The age above which the media server holds things back, as an invitation is asked for. */
    case AgeLimit = 'age_limit';
}
