<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `log` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum LogField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** One line of what a service wrote, exactly as it wrote it. */
    case Line = 'line';

    /** Which of a service's two mouths a line came out of. */
    case Stream = 'stream';
}
