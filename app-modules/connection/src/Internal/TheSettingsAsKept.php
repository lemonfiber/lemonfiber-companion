<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\DaysAsked;
use Modules\Kernel\Api\DeviceIdIsUnfit;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Unsealed;

/**
 * The phone's settings, written down and read back in every shape ever kept.
 *
 * A setting is carried over from any shape an earlier build wrote, never
 * discarded: an operator's choice is theirs, and a release is no reason to
 * forget it. Each setting is read on its own, so one this build cannot make out
 * reads as its standard and costs none of the others.
 */
final readonly class TheSettingsAsKept
{
    /** Where the lock's time away is written. */
    private const string LOCK_AFTER = 'lock_after';

    /** Where how long readings are kept is written: a count of days, or {@see UNTIL_REMOVED}. */
    private const string KEEP_READINGS = 'keep_readings';

    /** Written in place of a count where readings are kept until they are removed. */
    private const string UNTIL_REMOVED = 'until_removed';

    /** Where the id this install plays under is written. */
    private const string DEVICE = 'plays_as';

    public static function written(ThePhonesSettings $settings): Unsealed
    {
        return Unsealed::of((string) json_encode([
            self::LOCK_AFTER => $settings->lockAfter->name,
            self::KEEP_READINGS => $settings->readingsKept->either(
                days: static fn(int $days): WrittenAs => new WrittenAs($days),
                untilRemoved: static fn(): WrittenAs => new WrittenAs(self::UNTIL_REMOVED),
            )->value,
            self::DEVICE => $settings->device instanceof ThisDevice ? $settings->device->shown() : null,
        ]));
    }

    public static function read(Shape $shape, Unsealed $value): ThePhonesSettings
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true)),
        };
    }

    private static function inShapeOne(mixed $written): ThePhonesSettings
    {
        if (! is_array($written)) {
            return ThePhonesSettings::standard();
        }

        return new ThePhonesSettings(
            self::lockAfterIn(self::fieldIn($written, self::LOCK_AFTER)),
            self::readingsKeptIn(self::fieldIn($written, self::KEEP_READINGS)),
            self::deviceIn(self::fieldIn($written, self::DEVICE)),
        );
    }

    /**
     * What was written under this name, or null where nothing was.
     *
     * @param array<mixed> $written
     */
    private static function fieldIn(array $written, string $name): mixed
    {
        if (! array_key_exists($name, $written)) {
            return null;
        }

        return $written[$name];
    }

    private static function lockAfterIn(mixed $written): LockAfter
    {
        return is_string($written) ? LockAfter::named($written, otherwise: LockAfter::standard()) : LockAfter::standard();
    }

    /** The id this install plays under, or none where none was drawn or it is not one the core accepts. */
    private static function deviceIn(mixed $written): ?ThisDevice
    {
        if (! is_string($written)) {
            return null;
        }

        try {
            return ThisDevice::named($written);
        } catch (DeviceIdIsUnfit) {
            return null;
        }
    }

    private static function readingsKeptIn(mixed $written): HowLongReadingsAreKept
    {
        if ($written === self::UNTIL_REMOVED) {
            return HowLongReadingsAreKept::untilRemoved();
        }

        if (! is_int($written)) {
            return HowLongReadingsAreKept::standard();
        }

        return DaysAsked::counted($written)->either(
            allowed: static fn(HowLongReadingsAreKept $days): HowLongReadingsAreKept => $days,
            refused: static fn(): HowLongReadingsAreKept => HowLongReadingsAreKept::standard(),
        );
    }
}
