<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatMayReachThePhone;

/**
 * What the operator lets reach the phone from a stack, written down and read back in every shape ever kept.
 *
 * What does not read degrades rather than refuses, toward telling more: a kind
 * that does not read is read as switched on, and a floor that does not read as
 * every problem, since a choice the phone cannot make out is never a reason to
 * stop telling the operator something.
 */
final readonly class TheChoicesAsKept
{
    /** Where the kinds switched off are written. */
    private const string OFF = 'kinds_off';

    /** Where the lowest severity of problem told is written. */
    private const string FLOOR = 'problems_from';

    /** What is chosen, written. */
    public static function written(WhatMayReachThePhone $chosen): Unsealed
    {
        $off = [];

        foreach ($chosen->switchedOff() as $kind) {
            $off[] = $kind->value;
        }

        return Unsealed::of((string) json_encode([self::OFF => $off, self::FLOOR => $chosen->floor()->value]));
    }

    /** What is chosen, read back. */
    public static function read(Shape $shape, Unsealed $value): WhatMayReachThePhone
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true)),
        };
    }

    private static function inShapeOne(mixed $written): WhatMayReachThePhone
    {
        if (! is_array($written)) {
            return WhatMayReachThePhone::everything();
        }

        return WhatMayReachThePhone::from(self::floorIn(self::fieldIn($written, self::FLOOR)), ...self::offIn(self::fieldIn($written, self::OFF)));
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

    private static function floorIn(mixed $written): Severity
    {
        return (is_string($written) ? Severity::tryFrom($written) : null) ?? Severity::Advisory;
    }

    /** @return list<KindOfNotification> */
    private static function offIn(mixed $written): array
    {
        $off = [];

        foreach (is_array($written) ? $written : [] as $one) {
            $kind = is_string($one) ? KindOfNotification::tryFrom($one) : null;

            if ($kind instanceof KindOfNotification) {
                $off[] = $kind;
            }
        }

        return $off;
    }
}
