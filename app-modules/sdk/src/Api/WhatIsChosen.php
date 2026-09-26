<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\QualityEnvelope;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Sdk\Api\Fields\QualityField;
use Modules\Sdk\Internal\WhatAQualityAnswerCarries;
use Modules\Sdk\Internal\Wire;

use function trim;

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
        if (! array_key_exists(QualityField::Choices->value, $data) || ! is_array($data[QualityField::Choices->value])) {
            throw QualityIsUnreadable::missing(QualityEnvelope::KIND->value, QualityField::Choices);
        }

        $found = [];
        $position = 0;

        foreach ($data[QualityField::Choices->value] as $row) {
            if (! is_array($row)) {
                throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, QualityField::Choices, WireField::Preset, $position);
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
            $position++;
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
            throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, QualityField::Choices, QualityField::NeedsTranscodingHere, $position);
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
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw QualityIsUnreadable::entry(QualityEnvelope::KIND->value, QualityField::Choices, $field, $position);
        }

        return $row[$field->value];
    }
}
