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

    /**
     * What the stack's services reach, attributed to them.
     *
     * A separate field from `ours` on the wire and a separate list on every
     * screen: the two are never merged.
     */
    case Theirs = 'theirs';

    /**
     * What lemonfiber sends on an installed plugin's behalf, attributed to it.
     *
     * A third list on the wire and on every screen, merged into neither of the
     * other two.
     */
    case OnBehalf = 'on_behalf';

    /**
     * Where a request goes.
     *
     * A list on lemonfiber's own requests and one string on a service's or a
     * plugin's — one case for all, because it is one word on the wire naming
     * one idea.
     */
    case Destination = 'destination';

    /** Exactly what travels in one of lemonfiber's requests. */
    case Sends = 'sends';

    /** Whether this machine's settings let one of lemonfiber's requests go out. */
    case Allowed = 'allowed';

    /** The setting that switches one of lemonfiber's requests off. */
    case Switch = 'switch';

}
