<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use function sprintf;

/**
 * A month of the year, as a title's release day names it.
 *
 * Backed by the word its key is derived from, not by its number: the cases
 * are a calendar's order, and {@see numbered()} reads that order rather than
 * a second copy of it.
 */
enum Month: string
{
    case January = 'january';
    case February = 'february';
    case March = 'march';
    case April = 'april';
    case May = 'may';
    case June = 'june';
    case July = 'july';
    case August = 'august';
    case September = 'september';
    case October = 'october';
    case November = 'november';
    case December = 'december';

    /** The month a calendar numbers so, from one for January; the number is one a calendar has. */
    public static function numbered(int $month): self
    {
        return self::cases()[$month - 1];
    }

    /** The key its name is said under. */
    public function saidOnTheScreen(): string
    {
        return sprintf('household.month.%s', $this->value);
    }
}
