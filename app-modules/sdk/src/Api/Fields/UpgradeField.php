<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `upgrade` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum UpgradeField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether the operator said yes; without it nothing was asked of any service. */
    case Confirmed = 'confirmed';

    /** The kinds of media an upgrade covers. */
    case Media = 'media';

    /**
     * A kind of media: the one an upgrade covers, and the one a quality choice
     * is asked for, where it is for one kind rather than everything.
     */
    case MediaType = 'media_type';
}
