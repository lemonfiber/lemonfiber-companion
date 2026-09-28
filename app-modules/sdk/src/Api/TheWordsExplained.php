<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\GlossaryEnvelope;
use Lemonfiber\Sdk\Generated\WordEnvelope;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Sdk\Api\Fields\GlossaryField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `glossary` envelope into every word lemonfiber explains, and the
 * `word` envelope into the one word asked for.
 *
 * Both carry a word in one shape. Every word and short gloss is required, and
 * every other name and every form must be one.
 * Anything else is refused with {@see GlossaryIsUnreadable}, never defaulted;
 * the longer gloss is optional and arrives as `null` or absent where there is
 * none.
 */
final readonly class TheWordsExplained
{
    /**
     * The glossary, in the order the stack lists it.
     *
     * @param Envelope<mixed> $envelope the `glossary` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheGlossary
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw GlossaryIsUnreadable::missing(WireField::Data);
        }

        if (! array_key_exists(GlossaryField::Words->value, $data)) {
            throw GlossaryIsUnreadable::missing(GlossaryField::Words);
        }

        $rows = $data[GlossaryField::Words->value];

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw GlossaryIsUnreadable::missing(GlossaryField::Words);
        }

        $words = [];

        foreach ($rows as $position => $row) {
            $words[] = self::word(is_array($row) ? $row : [], $position);
        }

        return TheGlossary::of(...$words);
    }

    /**
     * The one word the stack was asked for.
     *
     * @param Envelope<mixed> $envelope the `word` envelope, as the client returned it
     */
    public static function one(Envelope $envelope): AWord
    {
        $data = self::entry(Wire::checked($envelope));

        if (! is_array($data)) {
            throw GlossaryIsUnreadable::asked(WireField::Data);
        }

        return self::word($data, null);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return GlossaryEnvelope::in($envelope)->data;
    }

    /**
     * The one word's payload, as it actually arrived, for {@see payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function entry(Envelope $envelope): mixed
    {
        return WordEnvelope::in($envelope)->data;
    }

    /**
     * One word, whether the glossary listed it or it was asked for alone.
     *
     * @param array<array-key, mixed> $row
     * @param int|null $position where the glossary listed it, or `null` for the one word asked for
     */
    private static function word(array $row, ?int $position): AWord
    {
        return AWord::explained(
            self::text($row, WireField::Word, $position),
            self::text($row, WireField::Short, $position),
            self::deep($row, $position),
            ...self::names($row, WireField::AlsoCalled, $position),
        )->writtenAs(WhatElseItIsCalled::formsOf(...self::names($row, WireField::Forms, $position)));
    }

    /**
     * The longer gloss: empty where absent or `null`, refused where blank or not text.
     *
     * @param array<array-key, mixed> $row
     */
    private static function deep(array $row, ?int $position): string
    {
        if (! array_key_exists(WireField::Deep->value, $row) || $row[WireField::Deep->value] === null) {
            return '';
        }

        return self::text($row, WireField::Deep, $position);
    }

    /**
     * What else the word is called, or the forms it is written in: a list,
     * every entry required to be a name.
     *
     * @param array<array-key, mixed> $row
     * @return list<string>
     */
    private static function names(array $row, NamesAWireField $field, ?int $position): array
    {
        if (! array_key_exists($field->value, $row)) {
            throw self::unreadable($position, $field);
        }

        $names = $row[$field->value];

        if (! is_array($names) || ! array_is_list($names)) {
            throw self::unreadable($position, $field);
        }

        $read = [];

        foreach ($names as $name) {
            if (! is_string($name) || trim($name) === '') {
                throw self::unreadable($position, $field);
            }

            $read[] = $name;
        }

        return $read;
    }

    /**
     * A required field of one word, as text.
     *
     * Blank as well as absent, because {@see AWord::explained()} refuses a
     * blank one with its own kind, which would travel past the adapter's catch
     * and reach the operator as a crash rather than as an obstacle.
     *
     * @param array<array-key, mixed> $row
     */
    private static function text(array $row, WireField $field, ?int $position): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw self::unreadable($position, $field);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw self::unreadable($position, $field);
        }

        return $said;
    }

    /** What a word that is not one is refused with: by its place in the glossary, or as the word asked for. */
    private static function unreadable(?int $position, NamesAWireField $field): GlossaryIsUnreadable
    {
        return $position === null ? GlossaryIsUnreadable::asked($field) : GlossaryIsUnreadable::word($position, $field);
    }
}
