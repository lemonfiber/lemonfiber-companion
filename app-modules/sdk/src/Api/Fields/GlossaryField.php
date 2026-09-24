<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `glossary` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum GlossaryField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every word lemonfiber explains. */
    case Words = 'words';

    /** The word itself. */
    case Word = 'word';

    /** What it means, in a line. */
    case Short = 'short';

    /** What it means at length. */
    case Deep = 'deep';

    /** What else it is called. */
    case AlsoCalled = 'also_called';
}
