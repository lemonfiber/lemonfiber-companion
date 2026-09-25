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
     * Which request a decision is about, when one is sent.
     *
     * A second case for one number, because the wire says it twice under two
     * names: a reading calls it `id` inside the row it belongs to, and an
     * action asks for `request` because nothing around it says which kind of
     * thing is being named. One case serving both would be this app deciding
     * they are the same word, which is a fact about the contract and not about
     * this enum.
     */
    case Request = 'request';

    /**
     * The sentences a member is owed, written to them by the core.
     *
     * A list of strings and never parts to assemble: what a surface renders
     * here is what the core wrote, because a surface composing its own wording
     * from a policy and a standing would be a second voice able to disagree
     * with it.
     */
    case ToHandOver = 'to_hand_over';

    /** What one member has asked their stack for. */
    case Requests = 'requests';

    /** Whether somebody has set a password on their account, rather than an invitation nobody took up. */
    case Claimed = 'claimed';
}
