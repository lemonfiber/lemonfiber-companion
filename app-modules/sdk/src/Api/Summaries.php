<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DashboardEnvelope;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Sdk\Api\Fields\DashboardField;
use Modules\Sdk\Internal\Wire;

/**
 * The health summary out of a `dashboard` envelope, and nothing else from it.
 *
 * The summary is the one part of the dashboard a screen here draws. The rest
 * of the envelope is recorded field by field as something this app has decided
 * not to read, which is a decision rather than an oversight.
 *
 * **Every field is refused rather than defaulted.** A summary missing its count
 * or its standing is not a summary with fewer things wrong, and a reader that
 * filled the gap would be the app deciding something the core did not.
 */
final readonly class Summaries
{
    /** @param Envelope<mixed> $envelope */
    public static function in(Envelope $envelope): TheHealthSummary
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SummaryIsUnreadable::missing(WireField::Data);
        }

        $health = self::health($data);

        return TheHealthSummary::of(
            self::standing(self::text($health, WireField::Standing)),
            self::wanting($health),
            self::worst($health),
            ...self::affected($health),
        );
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return DashboardEnvelope::in($envelope)->data;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function health(array $data): array
    {
        if (! array_key_exists(DashboardField::Health->value, $data)) {
            throw SummaryIsUnreadable::missing(DashboardField::Health);
        }

        $health = $data[DashboardField::Health->value];

        if (! is_array($health)) {
            throw SummaryIsUnreadable::missing(DashboardField::Health);
        }

        return $health;
    }

    /** @param array<mixed> $health */
    private static function text(array $health, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $health)) {
            throw SummaryIsUnreadable::missing($field);
        }

        $said = $health[$field->value];

        if (! is_string($said)) {
            throw SummaryIsUnreadable::missing($field);
        }

        return $said;
    }

    private static function standing(string $said): HowItStands
    {
        return HowItStands::tryFrom($said) ?? throw SummaryIsUnreadable::standing($said);
    }

    /** @param array<mixed> $health */
    private static function wanting(array $health): int
    {
        if (! array_key_exists(DashboardField::WantingAttention->value, $health)) {
            throw SummaryIsUnreadable::missing(DashboardField::WantingAttention);
        }

        $count = $health[DashboardField::WantingAttention->value];

        if (! is_int($count)) {
            throw SummaryIsUnreadable::missing(DashboardField::WantingAttention);
        }

        return $count;
    }

    /**
     * The worst thing named, or nothing where the core named nothing.
     *
     * The contract lets it be absent and lets it be null, and both mean the
     * same: nothing is wrong enough to name. Anything else is refused.
     *
     * @param array<mixed> $health
     */
    private static function worst(array $health): string
    {
        if (! array_key_exists(DashboardField::Worst->value, $health)) {
            return '';
        }

        $said = $health[DashboardField::Worst->value];

        if ($said === null) {
            return '';
        }

        if (! is_string($said)) {
            throw SummaryIsUnreadable::missing(DashboardField::Worst);
        }

        return $said;
    }

    /**
     * @param array<mixed> $health
     *
     * @return list<AnAffectedItem>
     */
    private static function affected(array $health): array
    {
        if (! array_key_exists(DashboardField::Affected->value, $health)) {
            throw SummaryIsUnreadable::missing(DashboardField::Affected);
        }

        $rows = $health[DashboardField::Affected->value];

        if (! is_array($rows)) {
            throw SummaryIsUnreadable::missing(DashboardField::Affected);
        }

        $affected = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw SummaryIsUnreadable::inItem($position, DashboardField::Affected);
            }

            $affected[] = self::item($row, $position);
            $position++;
        }

        return $affected;
    }

    /** @param array<mixed> $row */
    private static function item(array $row, int $position): AnAffectedItem
    {
        return AnAffectedItem::of(
            Check::of(self::saidIn($row, WireField::Check, $position)),
            self::severity(self::saidIn($row, WireField::Severity, $position), $position),
            self::saidIn($row, WireField::Summary, $position),
            self::saidIn($row, WireField::Meaning, $position),
            Remedies::of(...self::remedies($row, $position)),
            WhatFollowedFromIt::of(...self::lines($row, DashboardField::Downstream, $position)),
        );
    }

    /** @param array<mixed> $row */
    private static function saidIn(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw SummaryIsUnreadable::inItem($position, $field);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw SummaryIsUnreadable::inItem($position, $field);
        }

        return $said;
    }

    private static function severity(string $said, int $position): Severity
    {
        return Severity::tryFrom($said) ?? throw SummaryIsUnreadable::severity($said, $position);
    }

    /**
     * @param array<mixed> $row
     *
     * @return list<Remedy>
     */
    private static function remedies(array $row, int $position): array
    {
        $remedies = [];

        foreach (self::lines($row, WireField::Remedies, $position) as $action) {
            $remedies[] = Remedy::of($action);
        }

        return $remedies;
    }

    /**
     * A list of sentences, every one of them text.
     *
     * @param array<mixed> $row
     *
     * @return list<string>
     */
    private static function lines(array $row, NamesAWireField $field, int $position): array
    {
        if (! array_key_exists($field->value, $row)) {
            throw SummaryIsUnreadable::inItem($position, $field);
        }

        $said = $row[$field->value];

        if (! is_array($said)) {
            throw SummaryIsUnreadable::inItem($position, $field);
        }

        $lines = [];

        foreach ($said as $line) {
            if (! is_string($line)) {
                throw SummaryIsUnreadable::inItem($position, $field);
            }

            $lines[] = $line;
        }

        return $lines;
    }
}
