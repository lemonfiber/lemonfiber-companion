<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\OneHome;

use function array_filter;
use function array_merge;
use function array_values;
use function in_array;

/**
 * A class constant holding a value that another class also holds: the
 * constant, and the other homes of its value.
 */
final readonly class Clash
{
    /** @param list<Home> $others */
    private function __construct(public Home $home, public array $others) {}

    /**
     * Every clash among the values a collector gathered, file by file.
     *
     * Two homes clash where they are in different classes and one can refer to
     * the other; equal values out of each other's reach only coincide. An enum
     * case is reported only as another's home, and an exempted constant is left
     * out altogether.
     *
     * @param array<string, list<list<array{string, string, int, bool}>>> $collected
     * @param list<string>                                                 $exempted
     *
     * @return list<self>
     */
    public static function among(array $collected, array $exempted, Reach $reach): array
    {
        $clashes = [];

        foreach (self::homesByValue($collected, $exempted) as $homes) {
            foreach (array_filter($homes, static fn(Home $home): bool => ! $home->isCase) as $home) {
                $others = array_values(array_filter(
                    $homes,
                    static fn(Home $other): bool => $other->class() !== $home->class() && $reach->joins($home->file, $other->file),
                ));

                if ($others !== []) {
                    $clashes[] = new self($home, $others);
                }
            }
        }

        return $clashes;
    }

    /**
     * @param array<string, list<list<array{string, string, int, bool}>>> $collected
     * @param list<string>                                                 $exempted
     *
     * @return array<list<Home>>
     */
    private static function homesByValue(array $collected, array $exempted): array
    {
        $homes = [];

        foreach ($collected as $file => $declarations) {
            foreach (array_merge(...$declarations) as [$value, $name, $line, $isCase]) {
                if (! in_array($name, $exempted, strict: true)) {
                    $homes[$value][] = new Home($value, $name, $file, $line, $isCase);
                }
            }
        }

        return $homes;
    }
}
