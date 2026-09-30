<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `version` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum VersionField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The version of lemonfiber answering. */
    case Binary = 'binary';

    /** What the container engine reports, where it could be asked. */
    case Compose = 'compose';

    /** The running release's notes, gathered by what kind of change each is. */
    case Groups = 'groups';

    /** The changes one group of the notes lists. */
    case Entries = 'entries';
}
