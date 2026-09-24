<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `outbound` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum OutboundField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every request lemonfiber makes on its own account. */
    case Ours = 'ours';

    /**
     * What the stack's services reach, attributed to them.
     *
     * A separate field from `ours` on the wire and a separate list on every
     * screen: the two are never merged.
     */
    case Theirs = 'theirs';

    /**
     * Where a request goes.
     *
     * A list on lemonfiber's own requests and one string on a service's —
     * one case for both, because it is one word on the wire naming one idea.
     */
    case Destination = 'destination';

    /** Why a request is made. */
    case Purpose = 'purpose';

    /** Exactly what travels in one of lemonfiber's requests. */
    case Sends = 'sends';

    /** Whether this machine's settings let one of lemonfiber's requests go out. */
    case Allowed = 'allowed';

    /** The setting that switches one of lemonfiber's requests off. */
    case Switch = 'switch';

    /** Which of lemonfiber's own requests a row is. */
    case Reach = 'reach';

    /**
     * Whether the stack ships a record of what a service reaches.
     *
     * False is *nobody knows*, and a service's destination is not read at
     * all where it is — an empty destination would otherwise say the service
     * reaches nothing.
     */
    case Recorded = 'recorded';
}
