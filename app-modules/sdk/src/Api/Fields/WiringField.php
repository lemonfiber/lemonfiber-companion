<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `wiring` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum WiringField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every link, in the order the stack declares them. */
    case Wired = 'wired';

    /** Every capability something asks for and nothing fills, naming what asked. */
    case Unfilled = 'unfilled';

    /** What one link reaches, and how that was settled. */
    case Reaches = 'reaches';

    /** Which arm of what a link reaches this is: asked for a capability, or kept to a named service. */
    case How = 'how';

    /** Where each service that claims a capability came from, by the service's name. */
    case Origins = 'origins';

    /** How an ask was settled, tagged by its own word. */
    case Settled = 'settled';

    /** Every candidate in a contest, named. */
    case Claimants = 'claimants';

    /** The claimants not chosen, where a choice is recorded. */
    case Over = 'over';

    /** Who chose, where a choice is recorded. */
    case Whose = 'whose';
}
