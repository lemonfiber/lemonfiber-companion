<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `in-flight` envelope that no other envelope this module reads carries.
 */
enum InFlightField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every download still coming down inside what would stop. */
    case Downloads = 'downloads';
}
