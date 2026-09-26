<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Modules\Sdk\Api\NamesAWireField;

use function trim;

/**
 * A field the contract requires, read as the type it declares or refused with the refusal the reader chose.
 *
 * The reader builds the refusal, because only the reader knows which list and
 * which entry the field was read from; this knows what an absent or mistyped
 * field looks like. Each guard is a test rather than `?? null` on the
 * subscript, which `C9` refuses.
 */
final readonly class Required
{
    /**
     * A list, as it arrived.
     *
     * @param  array<mixed> $table
     * @return array<mixed>
     */
    public static function rows(array $table, NamesAWireField $field, InvalidArgumentException $refused): array
    {
        if (! array_key_exists($field->value, $table) || ! is_array($table[$field->value])) {
            throw $refused;
        }

        return $table[$field->value];
    }

    /**
     * Text with something in it.
     *
     * @param array<mixed> $table
     */
    public static function text(array $table, NamesAWireField $field, InvalidArgumentException $refused): string
    {
        if (! array_key_exists($field->value, $table) || ! is_string($table[$field->value]) || trim($table[$field->value]) === '') {
            throw $refused;
        }

        return $table[$field->value];
    }

    /**
     * A yes or a no.
     *
     * @param array<mixed> $table
     */
    public static function flag(array $table, NamesAWireField $field, InvalidArgumentException $refused): bool
    {
        if (! array_key_exists($field->value, $table) || ! is_bool($table[$field->value])) {
            throw $refused;
        }

        return $table[$field->value];
    }

    /**
     * A whole number, which is what a port is on the wire.
     *
     * @param array<mixed> $table
     */
    public static function number(array $table, NamesAWireField $field, InvalidArgumentException $refused): int
    {
        if (! array_key_exists($field->value, $table) || ! is_int($table[$field->value])) {
            throw $refused;
        }

        return $table[$field->value];
    }
}
