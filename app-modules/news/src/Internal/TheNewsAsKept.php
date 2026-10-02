<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use function array_key_exists;
use function in_array;

use InvalidArgumentException;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;

/**
 * What the phone keeps about what is new on a stack, as written before it is sealed, and read back.
 *
 * A marker holds only what names and orders an item among its kind — a version,
 * a number, or a check and when it began — and nothing the stack says about it.
 *
 * What does not read degrades rather than refuses: a choice of kinds that does
 * not read is read as every kind marked, since a setting carried over is never a
 * reason to stop marking, and a marker that does not read as nothing seen, so the
 * kind starts again from what is current.
 */
final readonly class TheNewsAsKept
{
    private const string OFF = 'off';

    private const string SEEN = 'seen';

    private const string NAMED = 'known_as';

    private const string ORDER = 'order';

    /** What is kept, written. */
    public static function written(WhatIsKeptOfNews $kept): Unsealed
    {
        $off = [];

        foreach ($kept->switchedOff() as $kind) {
            $off[] = $kind->value;
        }

        $seen = [];

        foreach ($kept->everySeen() as $kind => $item) {
            $seen[$kind] = [self::NAMED => $item->named(), self::ORDER => $item->order()];
        }

        return Unsealed::of((string) json_encode([self::OFF => $off, self::SEEN => $seen]));
    }

    /** What is kept, read back. */
    public static function read(Shape $shape, Unsealed $value): WhatIsKeptOfNews
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true)),
        };
    }

    private static function inShapeOne(mixed $written): WhatIsKeptOfNews
    {
        return WhatIsKeptOfNews::of(self::offIn(self::fieldIn($written, self::OFF)), self::seenIn(self::fieldIn($written, self::SEEN)));
    }

    /** @return list<KindOfNews> */
    private static function offIn(mixed $named): array
    {
        $off = [];

        foreach (is_array($named) ? $named : [] as $one) {
            $kind = is_string($one) ? KindOfNews::tryFrom($one) : null;

            if ($kind instanceof KindOfNews && ! in_array($kind, $off, strict: true)) {
                $off[] = $kind;
            }
        }

        return $off;
    }

    /** @return array<string, AnItem> */
    private static function seenIn(mixed $written): array
    {
        $seen = [];

        foreach (KindOfNews::cases() as $kind) {
            $item = self::itemIn($kind, self::fieldIn($written, $kind->value));

            if ($item instanceof AnItem) {
                $seen[$kind->value] = $item;
            }
        }

        return $seen;
    }

    private static function itemIn(KindOfNews $kind, mixed $written): ?AnItem
    {
        $named = self::fieldIn($written, self::NAMED);
        $order = self::fieldIn($written, self::ORDER);

        return is_string($named) && is_int($order) ? self::itemOf($kind, $named, $order) : null;
    }

    /** One field of what was written, or nothing where it is not there. */
    private static function fieldIn(mixed $written, string $name): mixed
    {
        return is_array($written) && array_key_exists($name, $written) ? $written[$name] : null;
    }

    /** The item of this kind those name and order, or nothing where they cannot. */
    private static function itemOf(KindOfNews $kind, string $named, int $order): ?AnItem
    {
        try {
            return match ($kind) {
                KindOfNews::Update => AnItem::anUpdate($named),
                KindOfNews::Request => AnItem::aRequest($order),
                KindOfNews::Problem => AnItem::aProblem($named, Instant::atEpochSeconds($order)),
            };
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
