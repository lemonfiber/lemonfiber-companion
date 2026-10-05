<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `substitution` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SubstitutionField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The choice itself: what fills the capability, what filled it, and what it costs. */
    case Substitution = 'substitution';

    /** Every service that asks for the capability being filled. */
    case AskedBy = 'asked_by';

    /** What the choice would leave unfilled, each with the service that would lose it. */
    case LeavesUnfilled = 'leaves_unfilled';
}
