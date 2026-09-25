<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `bandwidth` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum BandwidthField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Where the line a stack shares with its household stands. */
    case Restraint = 'restraint';

    /** What is worth knowing about a reading of the line before trusting it. */
    case Cautions = 'cautions';

    /**
     * The download direction of the line: its limit, or a figure measured.
     *
     * One case for both, as `destination` is: one word on the wire naming one
     * direction, under whichever parent is being read.
     */
    case Down = 'down';

    /** The upload direction of the line, read as {@see self::Down} is. */
    case Up = 'up';

    /** A direction's limit and the line it was measured against, in one sentence. */
    case Says = 'says';

    /** What is outside every limit on the line. */
    case Untouched = 'untouched';

    /** What the line was measured to carry, where anything measured it. */
    case Capacity = 'capacity';

    /** Where a figure for the line came from: declared or observed. */
    case Source = 'source';

    /** When the line was measured, in seconds since the epoch. */
    case Taken = 'taken';

    /** Whether the path the line was measured over goes through the tunnel. */
    case ThroughTunnel = 'through_tunnel';

    /** The monthly cap, where one was declared. */
    case Cap = 'cap';

    /** A cap's allowance, in bytes. */
    case Monthly = 'monthly';

    /** What reaching a cap does. */
    case Exceeded = 'exceeded';

    /** Where the month stands against a declared cap. */
    case Reached = 'reached';

    /** What a spent cap is doing to the figures. */
    case Acting = 'acting';
}
