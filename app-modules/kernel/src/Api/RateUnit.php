<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function sprintf;

/**
 * The unit a line's speed is said in, and the only place its bands are decided.
 *
 * {@see SizeUnit}'s sibling, and apart from it because a speed is not a size.
 * A line is sold, advertised and complained about in bits a second, and the
 * stack's own sentences about it say `Mbit/s` — so a figure in bytes beside
 * them would be the one number on the screen that does not add up with the
 * rest. And the smallest band is far smaller: an upload is often a few hundred
 * kilobits, which a scale starting at megabytes can only draw as nought.
 */
enum RateUnit: string
{
    case Kilobits = 'kilobits';

    case Megabits = 'megabits';

    case Gigabits = 'gigabits';

    /** How many bits a second one of this unit is, which is what the bands are. */
    public function bits(): int
    {
        return match ($this) {
            self::Kilobits => DecimalPrefix::Kilo->value,
            self::Megabits => DecimalPrefix::Mega->value,
            self::Gigabits => DecimalPrefix::Giga->value,
        };
    }

    /** The unit above this one, or this one where there is none, for {@see SizeUnit::next()}'s reason. */
    public function next(): self
    {
        return array_find(
            self::cases(),
            fn(self $unit): bool => $unit->bits() > $this->bits(),
        ) ?? $this;
    }

    /** What this is called on a screen, as a key built from the case. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.rate.%s', $this->value);
    }
}
