<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `playing` envelope that no other envelope this module reads carries.
 *
 * The rest — `available`, `findings`, `sessions`, `title`, `device` and `season`
 * — are shared, and named once in {@see \Modules\Sdk\Api\WireField}.
 */
enum PlayingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The member a session plays for, by name. */
    case Member = 'member';

    /** Whether a session is paused. */
    case Paused = 'paused';

    /** The series an episode belongs to. */
    case Series = 'series';

    /** An episode's number in its season. */
    case Episode = 'episode';
}
