<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function intdiv;

use Modules\Kernel\Api\SecondsIn;

/**
 * The lengths a lift of the line's limits is offered at, in seconds, besides a number of minutes typed.
 */
enum HowLongALiftLasts: int
{
    case AQuarterOfAnHour = SecondsIn::AnHour->value / 4;

    case AnHour = SecondsIn::AnHour->value;

    case FourHours = SecondsIn::AnHour->value * 4;

    /** The length offered at these minutes, or none. */
    public static function ofMinutes(string $typed): ?self
    {
        foreach (self::cases() as $case) {
            if ((string) $case->minutes() === $typed) {
                return $case;
            }
        }

        return null;
    }

    /** The minutes a lift of this length is asked for as. */
    public function minutes(): int
    {
        return intdiv($this->value, SecondsIn::AMinute->value);
    }

    /** The catalogue key its chip is labelled with. */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::AQuarterOfAnHour => 'stacks.line_change.lift.offered.quarter_hour',
            self::AnHour => 'stacks.line_change.lift.offered.hour',
            self::FourHours => 'stacks.line_change.lift.offered.four_hours',
        };
    }
}
