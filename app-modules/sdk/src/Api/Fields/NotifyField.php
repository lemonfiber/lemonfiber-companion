<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/** The `notify` envelope's fields no other envelope shares. */
enum NotifyField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every back-end, in the order it was added. */
    case BackEnds = 'backends';

    /** The back-end an unread record is about. */
    case BackEnd = 'backend';

    /** The host and topic or path a back-end reaches, with no credential. */
    case Where = 'where';

    /** Who chose a back-end. */
    case ChosenBy = 'chosen_by';

    /** Where a hosted back-end is hosted. */
    case Hosted = 'hosted';

    /** The host a hosted back-end runs at. */
    case Host = 'host';

    /** A back-end's proof, once it is proven. */
    case Proven = 'proven';

    /** A back-end's newest delivery. */
    case Last = 'last';

    /** Every delivery failed since a back-end's last confirmed one. */
    case Failures = 'failures';

    /** A delivery still to be tried again. */
    case Retry = 'retry';

    /** Which attempt a retry is. */
    case Attempt = 'attempt';

    /** A delivery's failure, with the back-end's reason. */
    case Failed = 'failed';
}
