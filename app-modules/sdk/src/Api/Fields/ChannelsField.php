<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `channels` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ChannelsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The live TV channels the member may watch. */
    case Channels = 'channels';

    /** When the programme on a channel now ends, where the guide says. */
    case Ends = 'ends';
}
