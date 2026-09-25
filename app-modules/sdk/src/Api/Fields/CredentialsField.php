<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `credentials` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum CredentialsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every credential the stack holds, whether or not it is present. */
    case Held = 'held';

    /** Everything that authenticates with one credential. */
    case Consumers = 'consumers';

    /** What is worth saying about one credential, where anything is. */
    case Advisory = 'advisory';

    /** What keeping credentials in files does and does not protect against. */
    case Protection = 'protection';

    /** What the store protects against. */
    case Against = 'against';

    /** What the store does not protect against. */
    case NotAgainst = 'not_against';
}
