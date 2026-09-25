<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `walkthrough` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum WalkthroughField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Which walk this was. */
    case Shape = 'shape';

    /** What it set out to prove, said so the operator knows what they watched. */
    case Proves = 'proves';

    /** Every line it said, in order. */
    case Lines = 'lines';

    /** The step a line narrates, or the one a walkthrough stopped at. */
    case Step = 'step';

    /** What a line says it is doing, in plain language. */
    case Said = 'said';

    /** What could have been walked instead, where nothing was chosen. */
    case Suggestions = 'suggestions';

    /** Whether the download was handed to the background rather than waited out. */
    case InBackground = 'in_background';

    /** Whether what was asked for was already here. */
    case AlreadyHere = 'already_here';

    /** What the import did with the file. */
    case Link = 'link';

    /** Where a finished walkthrough leaves the operator. */
    case Handover = 'handover';

    /** What to do next, in order, under the handover. */
    case Next = 'next';

    /** Where and why it stopped. */
    case Stopped = 'stopped';

    /** What the services involved were saying when it stopped. */
    case Logs = 'logs';
}
