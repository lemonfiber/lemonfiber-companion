<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `askable` envelope that no other envelope this module reads carries.
 *
 * The rest — `id`, `kind`, `title`, `year`, `approval` and `next` — are shared, and
 * named once in {@see \Modules\Sdk\Api\WireField}.
 */
enum AskableField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The titles on one page of a search. */
    case Titles = 'titles';

    /** Where one title stands in the house. */
    case Stands = 'stands';
}
