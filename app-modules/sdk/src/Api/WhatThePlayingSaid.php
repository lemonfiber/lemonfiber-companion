<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function array_values;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PlayingEnvelope;
use Modules\Kernel\Api\ASessionPlaying;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Sdk\Api\Fields\PlayingField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Wire;

/**
 * Reads what the household is playing out of the `playing` envelope, whether it was answered on asking or said on the stream.
 *
 * Each session's `medium` and `member_id` are not read: an episode is told
 * apart by its series, and a member is shown by name.
 */
final readonly class WhatThePlayingSaid
{
    /**
     * What the envelope says is playing.
     *
     * @param Envelope<mixed> $envelope the `playing` envelope
     */
    public static function in(Envelope $envelope): WhatTheHouseholdIsPlaying
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PlayingIsUnreadable::missing(WireField::Data->value);
        }

        if (! array_key_exists(WireField::Available->value, $data) || ! is_bool($data[WireField::Available->value])) {
            throw PlayingIsUnreadable::missing(WireField::Available->value);
        }

        if (! $data[WireField::Available->value]) {
            return WhatTheHouseholdIsPlaying::unread(...self::why($data));
        }

        $sessions = [];

        foreach (self::rows($data) as $position => $row) {
            $sessions[] = self::session($row, APath::beneath(WireField::Sessions->value, $position));
        }

        return WhatTheHouseholdIsPlaying::these(...$sessions);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return PlayingEnvelope::in($envelope)->data;
    }

    /**
     * @param  array<mixed> $data
     * @return list<string>
     */
    private static function why(array $data): array
    {
        if (! array_key_exists(WireField::Findings->value, $data) || ! is_array($data[WireField::Findings->value])) {
            throw PlayingIsUnreadable::missing(WireField::Findings->value);
        }

        return array_values(array_map(
            static fn(mixed $said): string => is_string($said) ? $said : throw PlayingIsUnreadable::missing(WireField::Findings->value),
            $data[WireField::Findings->value],
        ));
    }

    /**
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Sessions->value, $data) || ! is_array($data[WireField::Sessions->value])) {
            throw PlayingIsUnreadable::missing(WireField::Sessions->value);
        }

        return $data[WireField::Sessions->value];
    }

    private static function session(mixed $row, string $where): ASessionPlaying
    {
        if (! is_array($row)) {
            throw PlayingIsUnreadable::missing($where);
        }

        $member = self::text($row, $where, PlayingField::Member);
        $title = self::text($row, $where, WireField::Title);
        $device = self::text($row, $where, WireField::Device);
        $paused = self::paused($row, $where);

        if (! array_key_exists(PlayingField::Series->value, $row) || $row[PlayingField::Series->value] === null) {
            return ASessionPlaying::of($member, $title, $device, $paused);
        }

        $series = self::text($row, $where, PlayingField::Series);
        $season = self::number($row, $where, WireField::Season);
        $episode = self::number($row, $where, PlayingField::Episode);

        return $season === null || $episode === null
            ? ASessionPlaying::ofAnEpisode($member, $title, $device, $paused, $series)
            : ASessionPlaying::ofANumberedEpisode($member, $title, $device, $paused, $series, $season, $episode);
    }

    /** @param array<mixed> $row */
    private static function paused(array $row, string $where): bool
    {
        if (! array_key_exists(PlayingField::Paused->value, $row) || ! is_bool($row[PlayingField::Paused->value])) {
            throw PlayingIsUnreadable::missing(APath::beneath($where, PlayingField::Paused->value));
        }

        return $row[PlayingField::Paused->value];
    }

    /** @param array<mixed> $row */
    private static function number(array $row, string $where, NamesAWireField $field): ?int
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return null;
        }

        $said = $row[$field->value];

        return is_int($said) ? $said : throw PlayingIsUnreadable::missing(APath::beneath($where, $field->value));
    }

    /** @param array<mixed> $from */
    private static function text(array $from, string $where, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $from) || ! is_string($from[$field->value])) {
            throw PlayingIsUnreadable::missing(APath::beneath($where, $field->value));
        }

        return $from[$field->value];
    }
}
