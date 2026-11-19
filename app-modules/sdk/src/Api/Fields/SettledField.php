<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `settled` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SettledField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** How far setup got. */
    case Setup = 'setup';

    /** Where the library is kept, absent where no place is chosen. */
    case DataRoot = 'data_root';

    /** Which ways of downloading are set up. */
    case Protocols = 'protocols';

    /** Whether it downloads over Usenet. */
    case Usenet = 'usenet';

    /** Whether it downloads torrents. */
    case Torrent = 'torrent';

    /** The user and group the services run as. */
    case ServiceUser = 'service_user';

    /** The user id. */
    case Uid = 'uid';

    /** The group id. */
    case Gid = 'gid';
}
