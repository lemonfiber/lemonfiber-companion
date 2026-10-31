<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\QualityEnvelope;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatAChoiceMayBeMadeFrom;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WordsToChooseFrom;
use Modules\Sdk\Api\Fields\QualityField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatAQualityAnswerCarries;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `quality` envelope into the quality in force.
 *
 * Written the way {@see WhereTheDoorIs} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see QualityIsUnreadable}.
 *
 * **Every word is the stack's, unchanged.** A preset, what it means and what
 * an hour of it costs are drawn as they came; nothing here names a preset or
 * works out a size.
 *
 * What a choice may be made from is read off every answer, and what a choice
 * changes off the answer to one, where the stack says it.
 *
 * `overwritten` is not read: it is carried only where a preset was put back
 * over a hand-edit, and this app never asks for that.
 */
final readonly class WhatIsChosen
{
    /**
     * The quality in force, and what became of the choice.
     *
     * @param Envelope<mixed> $envelope the `quality` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheQualityChosen
    {
        $data = self::payload(Wire::checked($envelope));
        $kind = QualityEnvelope::KIND->value;

        if (! is_array($data)) {
            throw QualityIsUnreadable::missing($kind, WireField::Data);
        }

        return TheQualityChosen::reported(
            ThePresetsInForce::of(...self::choices($data)),
            self::music($data),
            WhatAQualityAnswerCarries::became($data, $kind),
            self::customised($data),
            self::offered($data),
            self::changes($data),
        );
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
        return QualityEnvelope::in($envelope)->data;
    }

    /**
     * The presets in force, in the stack's order.
     *
     * @param  array<mixed>         $data
     * @return list<APresetInForce>
     */
    private static function choices(array $data): array
    {
        if (! array_key_exists(WireField::Choices->value, $data) || ! is_array($data[WireField::Choices->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, WireField::Choices);
        }

        $found = [];

        foreach (InTheOrderSent::rows($data[WireField::Choices->value]) as $position => $row) {
            if (! is_array($row)) {
                throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, WireField::Choices, WireField::Preset, $position);
            }

            $found[] = APresetInForce::reported(
                self::inChoice($row, WireField::Scope, $position),
                self::inChoice($row, WireField::Preset, $position),
                self::inChoice($row, WireField::Means, $position),
                self::inChoice($row, QualityField::Resolution, $position),
                self::inChoice($row, WireField::SizePerHour, $position),
                self::inChoice($row, QualityField::Transcoding, $position),
                transcodesHere: self::transcodesHere($row, $position),
            );
        }

        return $found;
    }

    /**
     * Whether this machine would have to transcode one choice in software.
     *
     * @param array<mixed> $row
     */
    private static function transcodesHere(array $row, int $position): bool
    {
        if (! array_key_exists(QualityField::NeedsTranscodingHere->value, $row) || ! is_bool($row[QualityField::NeedsTranscodingHere->value])) {
            throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, WireField::Choices, QualityField::NeedsTranscodingHere, $position);
        }

        return $row[QualityField::NeedsTranscodingHere->value];
    }

    /**
     * The format chosen for music, or none where the stack sets none.
     *
     * @param array<mixed> $data
     */
    private static function music(array $data): WhatMusicIsSetTo
    {
        if (! array_key_exists(QualityField::Music->value, $data) || $data[QualityField::Music->value] === null) {
            return WhatMusicIsSetTo::unset();
        }

        $music = $data[QualityField::Music->value];

        if (! is_array($music)) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, QualityField::Music);
        }

        return WhatMusicIsSetTo::set(WhatAQualityAnswerCarries::format($music, QualityEnvelope::KIND->value, QualityField::Music));
    }

    /**
     * What a choice may be made from, each list in the stack's order.
     *
     * @param array<mixed> $data
     */
    private static function offered(array $data): WhatAChoiceMayBeMadeFrom
    {
        if (! array_key_exists(WireField::Offered->value, $data) || ! is_array($data[WireField::Offered->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, WireField::Offered);
        }

        $offered = $data[WireField::Offered->value];

        return WhatAChoiceMayBeMadeFrom::of(
            WordsToChooseFrom::under(QualityField::Presets->value, ...self::words($offered, QualityField::Presets)),
            WordsToChooseFrom::under(QualityField::Formats->value, ...self::words($offered, QualityField::Formats)),
            WordsToChooseFrom::under(QualityField::MediaTypes->value, ...self::words($offered, QualityField::MediaTypes)),
        );
    }

    /**
     * One list of words under what is offered.
     *
     * @param  array<mixed>  $offered
     * @return list<string>
     */
    private static function words(array $offered, QualityField $field): array
    {
        if (! array_key_exists($field->value, $offered) || ! is_array($offered[$field->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, $field);
        }

        $words = [];

        foreach (array_values($offered[$field->value]) as $position => $word) {
            if (! Required::isText($word)) {
                throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, WireField::Offered, $field, $position);
            }

            $words[] = $word;
        }

        return $words;
    }

    /**
     * What the choice changes, in the stack's words, or nothing where the answer is to no choice.
     *
     * @param array<mixed> $data
     */
    private static function changes(array $data): string
    {
        if (! array_key_exists(WireField::Changes->value, $data) || $data[WireField::Changes->value] === null) {
            return '';
        }

        if (! is_string($data[WireField::Changes->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, WireField::Changes);
        }

        return $data[WireField::Changes->value];
    }

    /**
     * Whether the configuration was edited by hand.
     *
     * @param array<mixed> $data
     */
    private static function customised(array $data): bool
    {
        if (! array_key_exists(QualityField::Customised->value, $data) || ! is_bool($data[QualityField::Customised->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, QualityField::Customised);
        }

        return $data[QualityField::Customised->value];
    }

    /**
     * A required field of one choice, as text.
     *
     * @param array<mixed> $row
     */
    private static function inChoice(array $row, NamesAWireField $field, int $position): string
    {
        return Required::text($row, $field, QualityIsUnreadable::entry(QualityEnvelope::KIND->value, WireField::Choices, $field, $position));
    }
}
