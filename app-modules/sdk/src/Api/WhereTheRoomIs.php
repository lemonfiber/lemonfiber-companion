<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SpaceEnvelope;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\HowAVolumeWasRead;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhereADownloadStands;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Sdk\Api\Fields\SpaceField;
use Modules\Sdk\Internal\Wire;

use function sprintf;
use function trim;

/**
 * Reads the `space` envelope into what the kernel knows about how full a machine is.
 *
 * Every figure is required to be a count, every word to be one this app has a
 * case for, and every list entry to be an entry; anything else is refused with
 * {@see SpaceIsUnreadable}, never defaulted. A figure the stack could not read
 * arrives as `null` and becomes {@see AnAmountOfRoom::unread()}, never nought.
 *
 * Only what the requirements ask for is read. The rest is recorded in
 * `WhatTheContractCarriesThatNothingReadsTest`, each with its reason.
 */
final readonly class WhereTheRoomIs
{
    /**
     * What the client reports as a ratio where there is none to report.
     *
     * The largest figure the field can carry. The core writes it for a torrent
     * added from files already on disk, which downloaded nothing, and reads it
     * back as no ratio (`ratio_reads`); read here the same way, and never
     * shown as a figure.
     */
    private const int NO_RATIO = 4_294_967_295;

    /**
     * How full a machine is, where the room went, and what is on its disk.
     *
     * @param Envelope<mixed> $envelope the `space` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhereTheRoomWent
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SpaceIsUnreadable::missing(WireField::Data->value);
        }

        return WhereTheRoomWent::measured(
            TheVolumes::of(...self::volumes($data)),
            self::level($data, SpaceField::Level->value),
            TheAccount::of(...self::account($data)),
            TheDownloadsOnDisk::of(...self::downloads($data)),
            halted: self::flag($data, SpaceField::Halted->value, SpaceField::Halted),
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
        return SpaceEnvelope::in($envelope)->data;
    }

    /**
     * Each volume the stack watches.
     *
     * @param  array<mixed>  $data
     * @return list<AVolume>
     */
    private static function volumes(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, SpaceField::Volumes) as $row) {
            if (! is_array($row)) {
                throw SpaceIsUnreadable::row(SpaceField::Volumes->value, $position);
            }

            $where = sprintf('%s[%d]', SpaceField::Volumes->value, $position);
            $holds = self::text($row, sprintf('%s.%s', $where, SpaceField::Role->value), SpaceField::Role);

            $found[] = AVolume::measured(
                WhatAVolumeHolds::tryFrom($holds) ?? throw SpaceIsUnreadable::word(sprintf('%s.%s', $where, SpaceField::Role->value), $holds, ...array_map(static fn(WhatAVolumeHolds $case): string => $case->value, WhatAVolumeHolds::cases())),
                self::point($row, $where),
                self::amount($row, $where, SpaceField::Free),
                self::amount($row, $where, SpaceField::Limit),
                self::count($row, sprintf('%s.%s', $where, SpaceField::Committed->value), SpaceField::Committed),
                self::amount($row, $where, SpaceField::Projected),
                self::level($row, sprintf('%s.%s', $where, SpaceField::Level->value)),
                self::reading($row, $where),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Where a volume is mounted, which is blank where the stack could not attribute it to one.
     *
     * @param array<mixed> $row
     */
    private static function point(array $row, string $where): string
    {
        if (! array_key_exists(SpaceField::Point->value, $row) || ! is_string($row[SpaceField::Point->value])) {
            throw SpaceIsUnreadable::missing(sprintf('%s.%s', $where, SpaceField::Point->value));
        }

        return $row[SpaceField::Point->value];
    }

    /**
     * A figure a volume's reading may leave out.
     *
     * @param array<mixed> $row
     */
    private static function amount(array $row, string $where, NamesAWireField $field): AnAmountOfRoom
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return AnAmountOfRoom::unread();
        }

        return AnAmountOfRoom::of(self::count($row, sprintf('%s.%s', $where, $field->value), $field), $field->value);
    }

    /**
     * How far a volume's figures can be relied on.
     *
     * @param array<mixed> $row
     */
    private static function reading(array $row, string $where): HowFreshAReadingIs
    {
        $path = sprintf('%s.%s', $where, SpaceField::Reading->value);

        if (! array_key_exists(SpaceField::Reading->value, $row) || ! is_array($row[SpaceField::Reading->value])) {
            throw SpaceIsUnreadable::missing($path);
        }

        $reading = $row[SpaceField::Reading->value];
        $as = self::text($reading, sprintf('%s.%s', $path, SpaceField::As->value), SpaceField::As);

        return match (HowAVolumeWasRead::tryFrom($as)) {
            HowAVolumeWasRead::Live => HowFreshAReadingIs::live(),
            HowAVolumeWasRead::AsOf => HowFreshAReadingIs::asOf(Instant::atEpochSeconds(self::count($reading, sprintf('%s.%s', $path, WireField::At->value), WireField::At))),
            null => throw SpaceIsUnreadable::word(sprintf('%s.%s', $path, SpaceField::As->value), $as, ...array_map(static fn(HowAVolumeWasRead $case): string => $case->value, HowAVolumeWasRead::cases())),
        };
    }

    /**
     * Where the room went, one line per category.
     *
     * @param  array<mixed>            $data
     * @return list<ALineOfTheAccount>
     */
    private static function account(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, SpaceField::Consumption) as $row) {
            if (! is_array($row)) {
                throw SpaceIsUnreadable::row(SpaceField::Consumption->value, $position);
            }

            $found[] = self::line($row, sprintf('%s[%d]', SpaceField::Consumption->value, $position));
            $position++;
        }

        return $found;
    }

    /**
     * One line of the account: what it is about, what it occupies, what getting it back costs.
     *
     * @param array<mixed> $row
     */
    private static function line(array $row, string $where): ALineOfTheAccount
    {
        $category = self::object($row, sprintf('%s.%s', $where, WireField::Category->value), WireField::Category);
        $of = self::text($category, sprintf('%s.%s.%s', $where, WireField::Category->value, SpaceField::Of->value), SpaceField::Of);
        $about = WhatALineIsAbout::tryFrom($of) ?? throw SpaceIsUnreadable::word(sprintf('%s.%s.%s', $where, WireField::Category->value, SpaceField::Of->value), $of, ...array_map(static fn(WhatALineIsAbout $case): string => $case->value, WhatALineIsAbout::cases()));
        $tally = self::object($row, sprintf('%s.%s', $where, SpaceField::Tally->value), SpaceField::Tally);
        $occupies = WhatItOccupies::counted(
            self::count($tally, sprintf('%s.%s.%s', $where, SpaceField::Tally->value, SpaceField::Logical->value), SpaceField::Logical),
            self::count($tally, sprintf('%s.%s.%s', $where, SpaceField::Tally->value, SpaceField::Physical->value), SpaceField::Physical),
        );
        $said = self::text($row, sprintf('%s.%s', $where, SpaceField::Reclaim->value), SpaceField::Reclaim);
        $costs = WhatGettingItBackCosts::tryFrom($said) ?? throw SpaceIsUnreadable::word(sprintf('%s.%s', $where, SpaceField::Reclaim->value), $said, ...array_map(static fn(WhatGettingItBackCosts $case): string => $case->value, WhatGettingItBackCosts::cases()));

        return $about === WhatALineIsAbout::Tree
            ? ALineOfTheAccount::forTheTree(self::text($category, sprintf('%s.%s.%s', $where, WireField::Category->value, WireField::Name->value), WireField::Name), $occupies, $costs)
            : ALineOfTheAccount::for($about, $occupies, $costs);
    }

    /**
     * The completed downloads on disk.
     *
     * @param  array<mixed>          $data
     * @return list<ADownloadOnDisk>
     */
    private static function downloads(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, SpaceField::Candidates) as $row) {
            if (! is_array($row)) {
                throw SpaceIsUnreadable::row(SpaceField::Candidates->value, $position);
            }

            $found[] = self::download($row, sprintf('%s[%d]', SpaceField::Candidates->value, $position));
            $position++;
        }

        return $found;
    }

    /**
     * One completed download, with what removing it costs where the stack says it costs anything.
     *
     * @param array<mixed> $row
     */
    private static function download(array $row, string $where): ADownloadOnDisk
    {
        return self::standing(
            $row,
            $where,
            self::text($row, sprintf('%s.%s', $where, WireField::Name->value), WireField::Name),
            self::count($row, sprintf('%s.%s', $where, WireField::Bytes->value), WireField::Bytes),
            self::consequence($row, $where),
        );
    }

    /**
     * What the stack says removing a download costs, or empty where it says nothing.
     *
     * @param array<mixed> $row
     */
    private static function consequence(array $row, string $where): string
    {
        if (! array_key_exists(SpaceField::Consequence->value, $row) || $row[SpaceField::Consequence->value] === null) {
            return '';
        }

        return self::text($row, sprintf('%s.%s', $where, SpaceField::Consequence->value), SpaceField::Consequence);
    }

    /**
     * A download built by where it stands, which decides whether it carries a ratio.
     *
     * @param array<mixed> $row
     */
    private static function standing(array $row, string $where, string $name, int $bytes, string $consequence): ADownloadOnDisk
    {
        $path = sprintf('%s.%s', $where, WireField::Standing->value);
        $standing = self::object($row, $path, WireField::Standing);
        $said = self::text($standing, sprintf('%s.%s', $path, WireField::Standing->value), WireField::Standing);

        return match (WhereADownloadStands::tryFrom($said)) {
            WhereADownloadStands::NeverImported => ADownloadOnDisk::neverImported($name, $bytes, $consequence),
            WhereADownloadStands::Seeding => ADownloadOnDisk::seeding($name, $bytes, self::ratio(self::count($standing, sprintf('%s.%s', $path, WireField::Ratio->value), WireField::Ratio)), $consequence),
            WhereADownloadStands::LeftAlone => ADownloadOnDisk::leftAlone($name, $bytes, $consequence),
            null => throw SpaceIsUnreadable::word(sprintf('%s.%s', $path, WireField::Standing->value), $said, ...array_map(static fn(WhereADownloadStands $case): string => $case->value, WhereADownloadStands::cases())),
        };
    }

    /** A ratio in hundredths, or none where the client wrote the figure that means none. */
    private static function ratio(int $hundredths): ARatio
    {
        return $hundredths === self::NO_RATIO ? ARatio::none() : ARatio::inHundredths($hundredths);
    }

    /**
     * Where the machine or one volume stands.
     *
     * @param array<mixed> $data
     */
    private static function level(array $data, string $where): WhereTheRoomStands
    {
        $said = self::text($data, $where, SpaceField::Level);

        return WhereTheRoomStands::tryFrom($said) ?? throw SpaceIsUnreadable::word($where, $said, ...array_map(static fn(WhereTheRoomStands $case): string => $case->value, WhereTheRoomStands::cases()));
    }

    /**
     * One list, required to be a list.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        return self::object($data, $list->value, $list);
    }

    /**
     * A field required to hold an object or a list.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function object(array $data, string $where, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw SpaceIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A count that cannot be below zero.
     *
     * @param array<mixed> $data
     */
    private static function count(array $data, string $where, NamesAWireField $field): int
    {
        if (! array_key_exists($field->value, $data) || ! is_int($data[$field->value]) || $data[$field->value] < 0) {
            throw SpaceIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A yes-or-no field.
     *
     * @param array<mixed> $data
     */
    private static function flag(array $data, string $where, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw SpaceIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A named field, as text an operator can be shown.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, string $where, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $data)) {
            throw SpaceIsUnreadable::missing($where);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw SpaceIsUnreadable::missing($where);
        }

        return $said;
    }
}
