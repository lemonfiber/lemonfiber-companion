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

    /** What checking a finding disturbs, where the read left it unverified for that reason. */
    case Disruptive = 'disruptive';

    /** The whole seconds a check that disturbs is bounded to, where the stack bounds it. */
    case Bounded = 'bounded';

    /** The check or group a diagnosis is narrowed to, to run a check that disturbs. */
    case Only = 'only';

    /** What a whole run came to, in the run's own judgement. */
    case Overall = 'overall';

    /** What the stack calls a finding's service in front of an operator. */
    case ServiceName = 'service_name';
}
