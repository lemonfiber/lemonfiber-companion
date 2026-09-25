<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `preview` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum PreviewField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The form a rehearsal is asked about, as the forms read's query names it. */
    case Form = 'form';

    /** The profiles a start would leave out, each with what it would need. */
    case Dropped = 'dropped';

    /** Which profile one of those is. */
    case Profile = 'profile';

    /** What that profile would have needed to be started. */
    case Needs = 'needs';
}
