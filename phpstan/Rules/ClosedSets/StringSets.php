<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\ClosedSets;

use function array_any;
use function array_map;
use function array_unique;
use function count;
use function max;

use PHPStan\Type\Type;

/** How many distinct strings a constant array holds, among its values or among its keys. */
final readonly class StringSets
{
    /** The distinct strings among a constant array's values, where every value is one; none where any is not. */
    public static function amongValues(Type $type): int
    {
        $most = 0;

        foreach ($type->getConstantArrays() as $array) {
            $most = max($most, self::distinct($array->getValueTypes()));
        }

        return $most;
    }

    /** The distinct strings among a constant array's keys, where every key is one; none where any is not. */
    public static function amongKeys(Type $type): int
    {
        $most = 0;

        foreach ($type->getConstantArrays() as $array) {
            $most = max($most, self::distinct($array->getKeyTypes()));
        }

        return $most;
    }

    /** @param array<Type> $items */
    private static function distinct(array $items): int
    {
        $strings = array_map(static fn(Type $item): array => $item->getConstantStrings(), $items);

        if (array_any($strings, static fn(array $found): bool => count($found) !== 1)) {
            return 0;
        }

        return count(array_unique(array_map(static fn(array $found): string => $found[0]->getValue(), $strings)));
    }
}
