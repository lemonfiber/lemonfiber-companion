<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `reset` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ResetField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The files whose edits a reset reverts, or would. */
    case Reverted = 'reverted';

    /** The lines of one of those files that differ, the operator's marked `-` and lemonfiber's `+`. */
    case Diff = 'diff';

    /** The connections whose drifted value a reset returns to lemonfiber's, or would. */
    case RevertedConnections = 'reverted_connections';
}
