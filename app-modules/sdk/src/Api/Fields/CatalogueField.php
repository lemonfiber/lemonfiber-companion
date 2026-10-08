<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `catalogue` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum CatalogueField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What going without one service costs the house. */
    case WithoutIt = 'without_it';

    /** The stack version whose catalogue stopped carrying one. */
    case RemovedIn = 'removed_in';

    /** What took a dropped service's place; absent or null where nothing did. */
    case ReplacedBy = 'replaced_by';
}
