<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;
use function filter_var;
use function is_array;
use function is_string;

use Modules\Kernel\Api\Instant;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\NewsIsUnreadable;
use Modules\Sdk\Api\WireField;

use function preg_match;

/**
 * How both news envelopes are read, field by field: what a stack lists as new,
 * and the newest of it its event stream names.
 *
 * The two carry the same lists under the same words, the stream's naming fewer
 * of each and less about each, so they are read by one set of hands and refuse
 * the same things the same way, with {@see NewsIsUnreadable}.
 */
final readonly class WhatANewsListHolds
{
    /**
     * The kinds the stack could not read, by the words the wire names them with.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    public static function unread(array $data): array
    {
        $words = [];

        foreach (self::listOf($data, WireField::Unread) as $word) {
            if (! is_string($word)) {
                throw NewsIsUnreadable::missing(WireField::Unread);
            }

            $words[] = $word;
        }

        return $words;
    }

    /**
     * One list of the payload, refused where it is not one.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<mixed>
     */
    public static function listOf(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value]) || ! array_is_list($data[$field->value])) {
            throw NewsIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * One entry of a list, refused where it is not an object.
     *
     * @return array<array-key, mixed>
     */
    public static function entry(mixed $entry, NamesAWireField $list, NamesAWireField $field, int $position): array
    {
        if (! is_array($entry)) {
            throw NewsIsUnreadable::item($list, $field, $position);
        }

        return $entry;
    }

    /**
     * A field every entry of its kind carries as text.
     *
     * @param array<array-key, mixed> $item
     */
    public static function text(array $item, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $item) || ! is_string($item[$field->value])) {
            throw NewsIsUnreadable::item($list, $field, $position);
        }

        return $item[$field->value];
    }

    /**
     * When a check in a list went wrong.
     *
     * Digits and nothing else, as `Records` reads a change's `at`, and within
     * an integer, which a long enough run of digits is not.
     *
     * @param array<array-key, mixed> $item
     */
    public static function onset(array $item, int $position): Instant
    {
        $onset = self::text($item, WireField::Problems, WireField::Onset, $position);
        $seconds = filter_var($onset, FILTER_VALIDATE_INT);

        if (preg_match('/^\d+$/', $onset) !== 1 || $seconds === false) {
            throw NewsIsUnreadable::item(WireField::Problems, WireField::Onset, $position);
        }

        return Instant::atEpochSeconds($seconds);
    }
}
