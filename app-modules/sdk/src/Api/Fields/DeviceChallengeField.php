<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `device-challenge` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum DeviceChallengeField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The challenge a phone's key signs to prove one mint. */
    case Challenge = 'challenge';
}
