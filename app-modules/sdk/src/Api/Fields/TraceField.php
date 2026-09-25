<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `trace` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum TraceField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What a trace is asked to follow. */
    case Term = 'term';

    /** The term the item was searched for by. */
    case Item = 'item';

    /** Whether a monitored item matched the term at all. */
    case Matched = 'matched';

    /** How sure the trace is of the item it followed. */
    case Confidence = 'confidence';

    /** The furthest stage the item reached. */
    case Furthest = 'furthest';

    /** The stages it passed through, in order. */
    case Stages = 'stages';

    /** The notable events in its history, oldest first. */
    case History = 'history';

    /** Why it stopped, where it plainly has. */
    case Stall = 'stall';

    /** How much of a series is here, season by season. */
    case Coverage = 'coverage';

    /** How many wanted parts are here. */
    case Have = 'have';

    /** How many parts nobody asked for. */
    case Unmonitored = 'unmonitored';

    /** Each season, in order. */
    case Seasons = 'seasons';

    /** A season's number. */
    case Season = 'season';

    /** The wanted parts not here yet. */
    case Outstanding = 'outstanding';

    /** An episode's number within its season. */
    case Number = 'number';
}
