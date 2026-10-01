<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

/**
 * This module's settings, written down and read back in every shape ever kept.
 *
 * A setting is carried over from any shape an earlier build wrote, never
 * discarded: an operator's choice is theirs, and a release is no reason to
 * forget it. A value this build cannot make out reads as the default.
 */
final readonly class TheSettingsAsKept
{
    /** Where the lock's time away is written. */
    private const string LOCK_AFTER = 'lock_after';

    public static function written(LockAfter $after): Unsealed
    {
        return Unsealed::of((string) json_encode([self::LOCK_AFTER => $after->name]));
    }

    public static function read(Shape $shape, Unsealed $value): LockAfter
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true)),
        };
    }

    private static function inShapeOne(mixed $written): LockAfter
    {
        if (! is_array($written) || ! array_key_exists(self::LOCK_AFTER, $written) || ! is_string($written[self::LOCK_AFTER])) {
            return LockAfter::standard();
        }

        return LockAfter::named($written[self::LOCK_AFTER], otherwise: LockAfter::standard());
    }
}
