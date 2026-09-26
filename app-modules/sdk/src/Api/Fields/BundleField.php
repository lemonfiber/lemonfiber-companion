<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `bundle` envelope that no other envelope this module reads carries, and the `support` action's arguments.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum BundleField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Everything a bundle holds, gathered, redacted and read back. */
    case Contents = 'contents';

    /** The files inside it, in the order a reader would want them. */
    case Pieces = 'pieces';

    /** What one file holds, already redacted. */
    case Body = 'body';

    /** How it was made, and what its operator chose. */
    case Terms = 'terms';

    /** How much of each service's logs it takes, in the stack's words. */
    case Window = 'window';

    /** Whether media filenames are shown: stated in the terms, and asked for on the action. */
    case Filenames = 'filenames';

    /** The settings it shows as they are, by name. */
    case Revealed = 'revealed';

    /** The version of lemonfiber that took it. */
    case Lemonfiber = 'lemonfiber';

    /** The version of the stack it was taken from. */
    case Stack = 'stack';

    /** Where it was written. Absent on a run that only described it. */
    case Path = 'path';

    /** Where it would be written, on a run that only described it. */
    case WouldGo = 'would_go';

    /** Whether to write the bundle, rather than say what one would hold. */
    case Write = 'write';

    /** How many log lines to take from each service. */
    case Logs = 'logs';

    /** The settings to show as they are, named as the bundle names them. */
    case Reveal = 'reveal';
}
