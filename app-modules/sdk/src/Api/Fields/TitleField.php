<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `title` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum TitleField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What it is about, or what happens in an episode. */
    case Overview = 'overview';

    /** How long it runs, in whole minutes. */
    case Minutes = 'minutes';

    /** The genres the server files it under. */
    case Genres = 'genres';

    /** The certificate it carries where the operator lives. */
    case Certificate = 'certificate';

    /** When it came out, as `YYYY-MM-DD`. */
    case Released = 'released';

    /** One season's episodes. */
    case Episodes = 'episodes';

    /** Where it streams from at the household's door. */
    case StreamFrom = 'stream_from';

    /** Why no location is stated, where none is. */
    case Unlocated = 'unlocated';
}
