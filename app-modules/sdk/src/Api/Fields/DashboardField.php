<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `dashboard` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum DashboardField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The one-line health summary, as the core computed it for every surface. */
    case Health = 'health';

    /** How many things want attention, counted by cause rather than by symptom. */
    case WantingAttention = 'wanting_attention';

    /** The worst thing, named, where anything is wrong. */
    case Worst = 'worst';

    /** Every thing counted as wrong, worst first. */
    case Affected = 'affected';

    /** What else is wrong because of one affected item. */
    case Downstream = 'downstream';
}
