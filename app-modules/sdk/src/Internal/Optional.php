<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use InvalidArgumentException;

use function is_int;
use function is_string;

use Modules\Sdk\Api\NamesAWireField;

/**
 * A field the contract carries only where it has something to say, read as the type it declares.
 *
 * {@see Required}'s other half. Absent and null are one answer, that nothing
 * was said; a value of another type is refused with the refusal the reader
 * chose, because a field present as the wrong thing is not a field left out.
 */
final readonly class Optional
{
    /**
     * Text, or empty where nothing was said.
     *
     * @param array<mixed> $table
     */
    public static function text(array $table, NamesAWireField $field, InvalidArgumentException $refused): string
    {
        if (! array_key_exists($field->value, $table) || $table[$field->value] === null) {
            return '';
        }

        return is_string($table[$field->value]) ? $table[$field->value] : throw $refused;
    }

    /**
     * A whole number, or null where nothing was said.
     *
     * @param array<mixed> $table
     */
    public static function number(array $table, NamesAWireField $field, InvalidArgumentException $refused): ?int
    {
        if (! array_key_exists($field->value, $table) || $table[$field->value] === null) {
            return null;
        }

        return is_int($table[$field->value]) ? $table[$field->value] : throw $refused;
    }
}
