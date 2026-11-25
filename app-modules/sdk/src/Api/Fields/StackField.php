<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `stack` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum StackField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The operator's own stack directory, by its path. */
    case Own = 'own';

    /** The `.env` file beside the Compose project. */
    case Env = 'env';

    /** The `docker compose` words that bring the stack up with no lemonfiber running. */
    case PlainUp = 'plain_up';

    /** How any action is run without a question being put. */
    case NonInteractive = 'non_interactive';
}
