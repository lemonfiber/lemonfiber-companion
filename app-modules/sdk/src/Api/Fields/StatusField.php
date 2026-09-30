<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `status` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum StatusField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The forms running, as the stack counts them. */
    case ActiveForms = 'active_forms';

    /** Containers on the machine that the stack's own configuration does not declare. */
    case Undeclared = 'undeclared';

    /** The services one service will not work without. */
    case DependsOn = 'depends_on';

    /** Which of a disturbance's two shapes this one is. */
    case Bound = 'bound';

    /** The shape with a clock on it. */
    case Bounded = 'bounded';

    /** How long that clock runs for. */
    case Seconds = 'seconds';

    /** What a disturbance with no clock on it waits for. */
    case Until = 'until';


    /** Bringing services up. */
    case Starting = 'starting';

    /** Taking services down. */
    case Stopping = 'stopping';

    /** Restarting services. */
    case Restarting = 'restarting';
}
