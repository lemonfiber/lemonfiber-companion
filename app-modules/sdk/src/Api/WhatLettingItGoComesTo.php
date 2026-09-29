<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StopSeedingEnvelope;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhereADownloadStands;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\StopSeedingField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `stop-seeding` envelope, as what stopping seeding one download would cost, or what it came to.
 *
 * Every answer carries the offer; only an answered one carries what became of
 * it. The offer is read for the question and the report for the yes, and a
 * finished yes with no report is refused rather than read as an offer: an
 * operator who agreed is owed what happened, and above all whether it was
 * only rehearsed.
 *
 * Every word is required to be one this app has a case for and every figure
 * a count; anything else is refused with {@see StopSeedingIsUnreadable}, never
 * defaulted.
 */
final readonly class WhatLettingItGoComesTo
{
    /**
     * What stopping seeding the download would cost, read before anything is let go.
     *
     * @param Envelope<mixed> $envelope the `stop-seeding` envelope a finished job answered with
     */
    public static function offerIn(Envelope $envelope): WhatLettingItGoCosts
    {
        $data = self::data($envelope);

        return WhatLettingItGoCosts::offered(
            self::download(self::table($data, StopSeedingField::Download->value, StopSeedingField::Download)),
            self::text($data, StopSeedingField::Goes->value, StopSeedingField::Goes),
            self::text($data, WireField::Agreement->value, WireField::Agreement),
        );
    }

    /**
     * What became of the download once the offer was answered.
     *
     * @param Envelope<mixed> $envelope the `stop-seeding` envelope a finished yes answered with
     */
    public static function goneIn(Envelope $envelope): ADownloadLetGo
    {
        $where = WireField::Gone->value;
        $gone = self::table(self::data($envelope), $where, WireField::Gone);

        return ADownloadLetGo::reported(
            self::text($gone, self::path($where, WireField::Name), WireField::Name),
            self::count($gone, self::path($where, WireField::Bytes), WireField::Bytes),
            WhetherItWasRehearsed::said(self::flag($gone, self::path($where, WireField::Rehearsed), WireField::Rehearsed)),
        );
    }

    /**
     * The payload, checked to be a table.
     *
     * @param  Envelope<mixed>     $envelope
     * @return array<array-key, mixed>
     */
    private static function data(Envelope $envelope): array
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StopSeedingIsUnreadable::missing(WireField::Data->value);
        }

        return $data;
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
        return StopSeedingEnvelope::in($envelope)->data;
    }

    /**
     * The download the offer is about, built by where it stands, which decides whether it carries a ratio.
     *
     * @param array<array-key, mixed> $row
     */
    private static function download(array $row): ADownloadOnDisk
    {
        $where = StopSeedingField::Download->value;
        $name = self::text($row, self::path($where, WireField::Name), WireField::Name);
        $bytes = self::count($row, self::path($where, WireField::Bytes), WireField::Bytes);
        $consequence = self::consequence($row, $where);
        $path = self::path($where, WireField::Standing);
        $standing = self::table($row, $path, WireField::Standing);
        $said = self::text($standing, self::path($path, WireField::Standing), WireField::Standing);

        return match (WhereADownloadStands::tryFrom($said)) {
            WhereADownloadStands::NeverImported => ADownloadOnDisk::neverImported($name, $bytes, $consequence),
            WhereADownloadStands::Seeding => ADownloadOnDisk::seeding($name, $bytes, self::ratio(self::count($standing, self::path($path, WireField::Ratio), WireField::Ratio)), $consequence),
            WhereADownloadStands::LeftAlone => ADownloadOnDisk::leftAlone($name, $bytes, $consequence),
            null => throw StopSeedingIsUnreadable::word(self::path($path, WireField::Standing), $said, ...array_map(static fn(WhereADownloadStands $case): string => $case->value, WhereADownloadStands::cases())),
        };
    }

    /**
     * What the stack says removing the download costs, or empty where it says nothing.
     *
     * @param array<array-key, mixed> $row
     */
    private static function consequence(array $row, string $where): string
    {
        if (! array_key_exists(WireField::Consequence->value, $row) || $row[WireField::Consequence->value] === null) {
            return '';
        }

        return self::text($row, self::path($where, WireField::Consequence), WireField::Consequence);
    }

    /** A ratio in hundredths, or none where the client wrote the figure that means none. */
    private static function ratio(int $hundredths): ARatio
    {
        return $hundredths === WhereTheRoomIs::NO_RATIO ? ARatio::none() : ARatio::inHundredths($hundredths);
    }

    /** Where a field sits, as a refusal names it: the path so far, then each name below it. */
    private static function path(string $where, NamesAWireField ...$below): string
    {
        $path = [$where];

        foreach ($below as $field) {
            $path[] = $field->value;
        }

        return implode('.', $path);
    }

    /**
     * A field required to hold a table.
     *
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function table(array $data, string $where, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw StopSeedingIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A count that cannot be below zero.
     *
     * @param array<array-key, mixed> $data
     */
    private static function count(array $data, string $where, NamesAWireField $field): int
    {
        if (! array_key_exists($field->value, $data) || ! is_int($data[$field->value]) || $data[$field->value] < 0) {
            throw StopSeedingIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A yes-or-no field.
     *
     * @param array<array-key, mixed> $data
     */
    private static function flag(array $data, string $where, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw StopSeedingIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A named field, as text an operator can be shown.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, string $where, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw StopSeedingIsUnreadable::missing($where);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw StopSeedingIsUnreadable::missing($where);
        }

        return $said;
    }
}
