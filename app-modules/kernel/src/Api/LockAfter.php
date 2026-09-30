<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How long the app may be out of sight before the lock asks again.
 *
 * The five choices the operator is offered, and no others, each as the
 * seconds it is. What the phone keeps is the case's name rather than its
 * seconds, so that a kept choice means the same choice after a release that
 * changes how long one of them is.
 */
enum LockAfter: int
{
    case Immediately = 0;

    case OneMinute = SecondsIn::AMinute->value;

    case FiveMinutes = 5 * SecondsIn::AMinute->value;

    case FifteenMinutes = 15 * SecondsIn::AMinute->value;

    case OneHour = SecondsIn::AnHour->value;

    /** What the lock does until the operator chooses: ask on every return. */
    public static function standard(): self
    {
        return self::Immediately;
    }

    /** The choice kept under this name, or the one given where no choice is. */
    public static function named(string $name, self $otherwise): self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return $otherwise;
    }

    /** How long that is. */
    public function howLong(): HowLong
    {
        return HowLong::ofSeconds($this->value);
    }

    /** The catalogue key the choice is said by. */
    public function said(): string
    {
        return match ($this) {
            self::Immediately => 'settings.after.immediately',
            self::OneMinute => 'settings.after.one_minute',
            self::FiveMinutes => 'settings.after.five_minutes',
            self::FifteenMinutes => 'settings.after.fifteen_minutes',
            self::OneHour => 'settings.after.one_hour',
        };
    }
}
