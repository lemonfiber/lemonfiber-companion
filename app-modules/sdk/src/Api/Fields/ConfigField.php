<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `config` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ConfigField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Everything the stack is set to, as the `config` envelope lists it. */
    case Settings = 'settings';

    /** One setting's name. */
    case Key = 'key';

    /** A proposed change, read against what is in force, and where it stands. */
    case Review = 'review';

    /** The difference a review is about, as it would be applied. */
    case Change = 'change';

    /** Where a proposed change stands. */
    case Stance = 'stance';

    /** What a setting would hold. */
    case To = 'to';

    /**
     * Why nothing was written, where the reason is not that nobody said yes.
     *
     * A different word on the wire from `refused`, which this enum already
     * carries for a request a household member was turned down for. Two cases
     * because they are two fields on two envelopes, and one case serving both
     * would be this app deciding they are the same thing.
     */
    case Refusal = 'refusal';
}
