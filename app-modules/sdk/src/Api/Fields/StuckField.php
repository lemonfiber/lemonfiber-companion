<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `stuck` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum StuckField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether a listing is short of what the stack actually holds. */
    case Incomplete = 'incomplete';

    /** The rows of a listing, where the envelope does not name them otherwise. */
    case Items = 'items';

    /** How far a stalled item got before it stopped. */
    case Stage = 'stage';

    /** The limits a reading carried: what the stack found and cannot act on. */
    case Unsupported = 'unsupported';
}
