<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `household` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum HouseholdField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** How big a request is thought to be, and whether anybody measured it. */
    case Estimate = 'estimate';

    /** Whether the figure beside it was measured rather than worked out. */
    case Measured = 'measured';

    /**
     * The sentences a member is owed, written to them by the core.
     *
     * A list of strings and never parts to assemble: what a surface renders
     * here is what the core wrote, because a surface composing its own wording
     * from a policy and a standing would be a second voice able to disagree
     * with it.
     */
    case ToHandOver = 'to_hand_over';

    /** Whether somebody has set a password on their account, rather than an invitation nobody took up. */
    case Claimed = 'claimed';
}
