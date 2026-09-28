<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `undo` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum UndoField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What putting a run back reversed, or would, in the order it did. */
    case Reversed = 'reversed';

    /** What it did not put back, or could not promise, each with why. */
    case Left = 'left';

    /** What going back means beyond the changes themselves; absent where nothing does. */
    case Noted = 'noted';
}
