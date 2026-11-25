<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `copying` envelope that no other envelope this module reads carries.
 *
 * The rest — `running`, `what`, `started`, `written`, `part`, `state`, `limit`
 * and `wrote` — are shared, and named once in {@see \Modules\Sdk\Api\WireField}.
 */
enum CopyingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The bytes a running copy will write. */
    case Whole = 'whole';
}
