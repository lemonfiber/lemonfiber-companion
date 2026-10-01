<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * The two choices of how long readings are kept that are not one of the
 * lengths offered by name.
 *
 * The value is what tapping the chip hands the screen, beside the name of each
 * length offered.
 */
enum NotACountOfDays: string
{
    /** Kept until the operator removes them. */
    case UntilRemoved = 'UntilRemoved';

    /** A count of days the operator types. */
    case Other = 'Other';

    /** The catalogue key the chip is said by. */
    public function said(): string
    {
        return match ($this) {
            self::UntilRemoved => 'settings.until_removed',
            self::Other => 'settings.other',
        };
    }
}
