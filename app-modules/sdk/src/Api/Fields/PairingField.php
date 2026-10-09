<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `pairing` envelope that no other envelope this module reads carries.
 */
enum PairingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The material itself, which the address, the fingerprint and the expiry are read out of. */
    case Material = 'material';

    /** The fingerprint in the short form a person compares. */
    case Compare = 'compare';

    /** When it stops being good, in seconds since the Unix epoch. */
    case Expires = 'expires';

    /** What replacing the certificate would cost every paired phone, in words any surface can show. */
    case Replacing = 'replacing';
}
