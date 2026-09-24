<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `history` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum HistoryField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /**
     * How far back a record goes, in the operator's terms.
     *
     * Required, and read before any change is: a trimmed record and one that
     * was always short look the same from their entries, and this is the only
     * field that says which.
     */
    case Horizon = 'horizon';

    /** What one change did, in the operator's terms. */
    case Did = 'did';

    /** The operation that made one change — a seed, a reconfigure, an applied fix. */
    case Operation = 'operation';

    /** What one change was made to. */
    case Target = 'target';

    /**
     * How many changes the operation behind one change made, it among them.
     *
     * An operation is the unit an operator agreed to, so this is what a
     * single line would take with it if it were undone.
     */
    case Alongside = 'alongside';

    /** What to do instead, where putting a change back stops short. */
    case Instead = 'instead';
}
