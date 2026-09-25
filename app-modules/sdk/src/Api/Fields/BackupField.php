<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `backup` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum BackupField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The older copies taking this one removed, by name. */
    case Pruned = 'pruned';

    /** How the copy's size stood against the time a copy is meant to take. */
    case Pace = 'pace';

    /** The bytes the copy came to. */
    case Moved = 'moved';

    /** The bytes a copy can come to and still finish in a minute. */
    case Budget = 'budget';

    /** Whether the copy is inside that. */
    case Brisk = 'brisk';

    /** Whether the copy was run to find out what would happen, writing nothing. */
    case Rehearsed = 'rehearsed';

    /** Whether the copy holds credentials. */
    case Sensitive = 'sensitive';
}
