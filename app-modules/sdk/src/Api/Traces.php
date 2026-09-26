<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\TraceEnvelope;
use Modules\Kernel\Api\AMomentInItsHistory;
use Modules\Kernel\Api\AnEpisodeNotHereYet;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\AStageItReached;
use Modules\Kernel\Api\HowFarItGot;
use Modules\Kernel\Api\HowMuchOfASeasonIsHere;
use Modules\Kernel\Api\HowMuchOfItIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\TheMomentsInItsHistory;
use Modules\Kernel\Api\TheStagesItReached;
use Modules\Kernel\Api\TheTraceSaysNothing;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Kernel\Api\WhereTheServicesDisagree;
use Modules\Sdk\Api\Fields\TraceField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `trace` envelope into where one item got to.
 *
 * Every word is required to be one this app has a case for, and every count
 * and sentence the contract requires to be one; anything else is refused with
 * {@see TraceIsUnreadable}, never defaulted. Nothing asked for is read as an
 * answer of its own, and what the rest of such an envelope says is not read.
 */
final readonly class Traces
{
    /** @param Envelope<mixed> $envelope the `trace` envelope, as the client returned it */
    public static function in(Envelope $envelope): WhereItGotTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw TraceIsUnreadable::missing(WireField::Data);
        }

        try {
            return self::read($data);
        } catch (TheTraceSaysNothing $why) {
            throw TraceIsUnreadable::because($why);
        }
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
        return TraceEnvelope::in($envelope)->data;
    }

    /** @param array<array-key, mixed> $data */
    private static function read(array $data): WhereItGotTo
    {
        $item = self::text($data, WireField::Item);

        if (! self::flag($data, TraceField::Matched)) {
            return WhereItGotTo::nothingAskedFor($item);
        }

        return WhereItGotTo::followed($item, WhatTheTraceFound::traced(
            self::sure($data),
            HowFarItGot::reached(self::stage($data, TraceField::Furthest), self::stages($data), self::optional($data, TraceField::Stall)),
            self::history($data),
            WhereTheServicesDisagree::of(...array_map(
                static fn(mixed $finding): string => is_string($finding) ? $finding : throw TraceIsUnreadable::missing(WireField::Findings),
                self::rows($data, WireField::Findings),
            )),
            self::here($data),
        ));
    }

    /** @param array<array-key, mixed> $data */
    private static function sure(array $data): HowSureTheTraceIs
    {
        $said = self::text($data, TraceField::Confidence);

        return HowSureTheTraceIs::tryFrom($said)
            ?? throw TraceIsUnreadable::word(TraceField::Confidence, $said, ...array_map(static fn(HowSureTheTraceIs $case): string => $case->value, HowSureTheTraceIs::cases()));
    }

    /** @param array<array-key, mixed> $row */
    private static function stage(array $row, NamesAWireField $field): Stage
    {
        $said = self::text($row, $field);

        return Stage::tryFrom($said)
            ?? throw TraceIsUnreadable::word($field, $said, ...array_map(static fn(Stage $case): string => $case->value, Stage::cases()));
    }

    /** @param array<array-key, mixed> $data */
    private static function stages(array $data): TheStagesItReached
    {
        $stages = [];

        foreach (self::rows($data, TraceField::Stages) as $row) {
            $stages[] = AStageItReached::recorded(
                self::stage(self::table($row, TraceField::Stages), WireField::Stage),
                ServiceId::called(self::text(self::table($row, TraceField::Stages), WireField::Service)),
                self::optional(self::table($row, TraceField::Stages), WireField::At),
            );
        }

        return TheStagesItReached::of(...$stages);
    }

    /** @param array<array-key, mixed> $data */
    private static function history(array $data): TheMomentsInItsHistory
    {
        $moments = [];

        foreach (self::rows($data, TraceField::History) as $row) {
            $moment = self::table($row, TraceField::History);
            $said = self::text($moment, WireField::Outcome);
            $happened = WhatHappenedToIt::tryFrom($said)
                ?? throw TraceIsUnreadable::word(WireField::Outcome, $said, ...array_map(static fn(WhatHappenedToIt $case): string => $case->value, WhatHappenedToIt::cases()));

            $moments[] = AMomentInItsHistory::recorded($happened, self::text($moment, WireField::At));
        }

        return TheMomentsInItsHistory::of(...$moments);
    }

    /**
     * How much of it is here: absent or `null` for a whole item, counted for a series.
     *
     * @param array<array-key, mixed> $data
     */
    private static function here(array $data): HowMuchOfItIsHere
    {
        if (! array_key_exists(TraceField::Coverage->value, $data) || $data[TraceField::Coverage->value] === null) {
            return HowMuchOfItIsHere::aWholeItem();
        }

        $coverage = self::table($data[TraceField::Coverage->value], TraceField::Coverage);
        $seasons = [];

        foreach (self::rows($coverage, TraceField::Seasons) as $row) {
            $season = self::table($row, TraceField::Seasons);
            $outstanding = [];

            foreach (self::rows($season, TraceField::Outstanding) as $part) {
                $episode = self::table($part, TraceField::Outstanding);
                $outstanding[] = AnEpisodeNotHereYet::numbered(
                    self::count($episode, TraceField::Season),
                    self::count($episode, TraceField::Number),
                    self::text($episode, WireField::Title),
                    self::stage($episode, WireField::Stage),
                );
            }

            $seasons[] = HowMuchOfASeasonIsHere::counted(
                self::count($season, TraceField::Season),
                self::count($season, TraceField::Have),
                self::count($season, WireField::Wanted),
                self::count($season, TraceField::Unmonitored),
                ...$outstanding,
            );
        }

        return HowMuchOfItIsHere::inParts(ASeriesCounted::counted(
            self::count($coverage, TraceField::Have),
            self::count($coverage, WireField::Wanted),
            self::count($coverage, TraceField::Unmonitored),
            ...$seasons,
        ));
    }

    /**
     * A field holding a list.
     *
     * @param array<array-key, mixed> $row
     * @return list<mixed>
     */
    private static function rows(array $row, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value]) || ! array_is_list($row[$field->value])) {
            throw TraceIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /**
     * One entry of a list, which must be a table.
     *
     * @return array<array-key, mixed>
     */
    private static function table(mixed $entry, NamesAWireField $field): array
    {
        return is_array($entry) ? $entry : throw TraceIsUnreadable::missing($field);
    }

    /** @param array<array-key, mixed> $row */
    private static function flag(array $row, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw TraceIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /** @param array<array-key, mixed> $row */
    private static function count(array $row, NamesAWireField $field): int
    {
        if (! array_key_exists($field->value, $row) || ! is_int($row[$field->value])) {
            throw TraceIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /**
     * A sentence the contract makes optional: empty where absent or `null`, refused where blank or not text.
     *
     * @param array<array-key, mixed> $row
     */
    private static function optional(array $row, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return '';
        }

        return self::text($row, $field);
    }

    /** @param array<array-key, mixed> $row */
    private static function text(array $row, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw TraceIsUnreadable::missing($field);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw TraceIsUnreadable::missing($field);
        }

        return $said;
    }
}
