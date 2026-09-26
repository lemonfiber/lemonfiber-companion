<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `self-update` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SelfUpdateField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What the update endpoint is asked about when the question is the running copy. */
    case ThisCopy = 'self';

    /** The tool that owns the running copy. */
    case Owner = 'owner';

    /** What updating leaves alone, and what it needs afterwards. */
    case Afterwards = 'afterwards';

    /** What a release brings besides the program, and when any of it is fetched. */
    case Carries = 'carries';

    /** Why availability could not be told. */
    case Untold = 'untold';
}
