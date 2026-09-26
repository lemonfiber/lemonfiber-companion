<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhereTheAskingStands;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * The shapes the `quality`, `music` and `upgrade` envelopes carry alike, read once.
 *
 * A music format sits under `music` on one and under `choice` on another;
 * what became of a choice is `disposition` on both; and what became of asking
 * a service is `outcome` on the music answer and on each kind an upgrade
 * covers. Each is read here, so the three readers cannot come to read one
 * shape three ways.
 *
 * `Internal` because it takes the payload as it arrived, which is a table no
 * other module is handed.
 */
final readonly class WhatAQualityAnswerCarries
{
    /**
     * What became of the choice, from the word the stack wrote.
     *
     * @param array<mixed> $data
     */
    public static function became(array $data, string $kind): WhatBecameOfTheChoice
    {
        if (! array_key_exists(WireField::Disposition->value, $data) || ! is_string($data[WireField::Disposition->value])) {
            throw QualityIsUnreadable::missing($kind, WireField::Disposition);
        }

        $said = $data[WireField::Disposition->value];

        return WhatBecameOfTheChoice::tryFrom($said)
            ?? throw QualityIsUnreadable::word($kind, WireField::Disposition, $said, ...array_map(static fn(WhatBecameOfTheChoice $case): string => $case->value, WhatBecameOfTheChoice::cases()));
    }

    /**
     * One music format, read off the table it sits in.
     *
     * @param array<mixed> $format
     */
    public static function format(array $format, string $kind, NamesAWireField $under): AFormatInForce
    {
        return AFormatInForce::reported(
            self::under($format, $kind, $under, WireField::Scope),
            self::under($format, $kind, $under, WireField::Format),
            self::under($format, $kind, $under, WireField::Means),
            self::under($format, $kind, $under, WireField::Targets),
            self::under($format, $kind, $under, WireField::SizePerHour),
            self::under($format, $kind, $under, WireField::Note),
        );
    }

    /**
     * What became of asking a service, read off the table that carries it.
     *
     * Absent and null are one answer, nothing asked, which is what the
     * contract says both mean.
     *
     * @param array<mixed> $table
     */
    public static function asking(array $table, string $kind): WhatBecameOfAskingIt
    {
        if (! array_key_exists(WireField::Outcome->value, $table) || $table[WireField::Outcome->value] === null) {
            return WhatBecameOfAskingIt::notAsked();
        }

        $outcome = $table[WireField::Outcome->value];

        if (! is_array($outcome)) {
            throw QualityIsUnreadable::missing($kind, WireField::Outcome);
        }

        $said = self::under($outcome, $kind, WireField::Outcome, WireField::State);
        $stands = WhereTheAskingStands::tryFrom($said)
            ?? throw QualityIsUnreadable::word($kind, WireField::State, $said, ...array_map(static fn(WhereTheAskingStands $case): string => $case->value, WhereTheAskingStands::cases()));

        return $stands === WhereTheAskingStands::Failed
            ? WhatBecameOfAskingIt::failed(self::under($outcome, $kind, WireField::Outcome, WireField::Detail))
            : WhatBecameOfAskingIt::asked($stands);
    }

    /**
     * A required field of a table under another, as text.
     *
     * @param array<mixed> $table
     */
    private static function under(array $table, string $kind, NamesAWireField $parent, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $table) || ! is_string($table[$field->value]) || trim($table[$field->value]) === '') {
            throw QualityIsUnreadable::under($kind, $parent, $field);
        }

        return $table[$field->value];
    }
}
