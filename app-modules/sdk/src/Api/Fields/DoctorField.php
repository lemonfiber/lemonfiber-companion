<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `doctor` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum DoctorField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The check whose finding explains this one, where another does. */
    case CausedBy = 'caused_by';

    /** What a whole run came to, in the run's own judgement. */
    case Overall = 'overall';

    /** The single thing to do about an unverified check. Singular on the wire. */
    case Remedy = 'remedy';

    /** How a single check turned out, as a tagged union. */
    case Verdict = 'verdict';

    /** The technical detail under a verdict, where the core gave one. */
    case Detail = 'detail';
}
