<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `guide` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum GuideField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** A channel's programmes across the window, each outside the member's limits left out. */
    case Programmes = 'programmes';

    /** Where the media server's guide ends on a channel inside the window. */
    case GuideUntil = 'guide_until';
}
