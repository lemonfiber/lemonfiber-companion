<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `hosting` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum HostingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every long-running command a machine has, hosted or not. */
    case Commands = 'commands';

    /** The service manager a machine has, or the absence of one this product configures. */
    case Manager = 'manager';

    /**
     * What to do instead, where this product cannot configure the platform.
     *
     * Read only on the `unsupported` arm and required there, for {@see Why}'s
     * reason: *not available here* with nothing after it is the empty box that
     * reads as *off*, and off is a thing somebody goes looking for a switch for.
     */
    case Instruction = 'instruction';

    /** What a long-running command does for as long as it runs, in one sentence. */
    case Guarantees = 'guarantees';

    /**
     * Where a hosted run writes what it would have said on a terminal.
     *
     * Read for the command an install acted on, because that is where
     * installing has to say its words go. Absent or null is the stack not
     * saying, which is a sentence of its own on the screen.
     */
    case Output = 'output';

    /** Whether an install started the command, which only installing does. */
    case Started = 'started';

    /** Every file an install wrote or a removal took back. */
    case Touched = 'touched';
}
