<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function array_key_exists;
use function is_array;
use function is_int;

/**
 * The envelope every record in this module is written in: its fields, and the
 * shape they were written in beside them.
 *
 * Each record keeps its own shape number, because each changes on its own; the
 * field that carries it is the same field in every record, and it is spelled
 * here once.
 */
final readonly class KeptInAShape
{
    /** The field that says which shape a record was written in. */
    private const string SHAPE = 'shape';

    /**
     * A record's fields, marked as written in this shape.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public static function written(int $shape, array $fields): array
    {
        return [self::SHAPE => $shape, ...$fields];
    }

    /**
     * Whether what was read is a record written in this shape.
     *
     * @phpstan-assert-if-true array<mixed> $found
     */
    public static function isIn(mixed $found, int $shape): bool
    {
        return is_array($found) && array_key_exists(self::SHAPE, $found) && $found[self::SHAPE] === $shape;
    }

    /**
     * Whether what was read is a record written in a later shape than this one.
     *
     * A build that is older than the record reads it as written by a newer
     * build of this app, rather than as nothing: the record is still there, and
     * the newer build reads it.
     */
    public static function isNewerThan(mixed $found, int $shape): bool
    {
        return is_array($found)
            && array_key_exists(self::SHAPE, $found)
            && is_int($found[self::SHAPE])
            && $found[self::SHAPE] > $shape;
    }
}
