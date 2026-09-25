<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_slice;
use function count;
use function explode;

/**
 * Where a reader calls itself on a shape that holds another of itself.
 *
 * An origin can hold the origin of what it replaced, so the reader that reads
 * one calls itself on a path that runs on by the same fields again:
 * `origin.replaced.from.replaced.from`. One time around is followed, which
 * reads every field the shape has; a second would read the same fields a level
 * deeper, and {@see WhatTheReadersRead} would add a level on every pass and
 * never settle.
 *
 * @phpstan-import-type Bindings from WhatAReaderNames
 */
final readonly class WhereAShapeHoldsItself
{
    /**
     * Whether a call enters a reader a second time around such a shape.
     *
     * @param Bindings $given
     */
    public static function isEnteredAgain(array $given): bool
    {
        foreach ($given['paths'] as $paths) {
            foreach ($paths as $path) {
                if (self::repeatsItsTail($path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Whether a path ends with the same run of fields twice over. */
    public static function repeatsItsTail(string $path): bool
    {
        $fields = explode('.', $path);
        $count = count($fields);

        for ($run = 1; $run * 2 <= $count; ++$run) {
            if (array_slice($fields, -$run) === array_slice($fields, -2 * $run, $run)) {
                return true;
            }
        }

        return false;
    }
}
