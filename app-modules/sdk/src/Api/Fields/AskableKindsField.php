<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `askable-kinds` envelope that no other envelope this module reads carries.
 *
 * The rest, `kinds` and `kind`, are shared, and named once in
 * {@see \Modules\Sdk\Api\WireField}.
 */
enum AskableKindsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The units of music or books a member can ask for under one kind. */
    case Units = 'units';
}
