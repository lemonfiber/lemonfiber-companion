<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `news-items` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum NewsItemsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The releases in the stack's record. */
    case Updates = 'updates';

    /** The checks found wrong. */
    case Problems = 'problems';

    /** When a check went wrong, in whole seconds since the epoch. */
    case Onset = 'onset';

    /** Who asked for a request. */
    case By = 'by';
}
