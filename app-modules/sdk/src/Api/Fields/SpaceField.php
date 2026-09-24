<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `space` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SpaceField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Where a machine, or one of its volumes, stands for room. */
    case Level = 'level';

    /** Whether the stack has stopped starting new downloads to keep its services writable. */
    case Halted = 'halted';

    /** The volumes a stack watches. */
    case Volumes = 'volumes';

    /** Which of the two volumes a reading is about. */
    case Role = 'role';

    /** Where a volume is mounted. */
    case Point = 'point';

    /** Bytes free on a volume. */
    case Free = 'free';

    /** A volume's own size, or its quota. */
    case Limit = 'limit';

    /** Bytes already on their way to landing on a volume. */
    case Committed = 'committed';

    /** What a volume will have free once what is on its way has landed. */
    case Projected = 'projected';

    /** How far a volume's figures can be relied on. */
    case Reading = 'reading';

    /** Which kind of reading it was. */
    case As = 'as';

    /** Where the room on a machine went, one line per category. */
    case Consumption = 'consumption';

    /** Which kind of category a line of the account is. */
    case Of = 'of';

    /** What a set of files occupies. */
    case Tally = 'tally';

    /** What files would take with nothing shared. */
    case Logical = 'logical';

    /** What a volume has lost to files. */
    case Physical = 'physical';

    /** What getting a line's room back would cost. */
    case Reclaim = 'reclaim';

    /** The completed downloads on a machine. */
    case Candidates = 'candidates';

    /** What removing a download costs, where it costs anything. */
    case Consequence = 'consequence';
}
