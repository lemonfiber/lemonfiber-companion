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

    /** One setting's name. */
    case Key = 'key';

    /** A proposed change, read against what is in force, and where it stands. */
    case Review = 'review';

    /** What a change newly asks the operator for. */
    case Opens = 'opens';

    /** What a change stops running, by service name. */
    case Stops = 'stops';

    /** The library paths the services hold, and what moving the data location does to each. */
    case Library = 'library';

    /** The hand-edit found in the configuration file. */
    case Edited = 'edited';

    /** What lemonfiber last wrote where a hand-edit was found. */
    case Wrote = 'wrote';

    /** What is still coming down, where a change would interrupt it. */
    case Active = 'active';

    /** Which way one download is coming down. */
    case Protocol = 'protocol';
}
