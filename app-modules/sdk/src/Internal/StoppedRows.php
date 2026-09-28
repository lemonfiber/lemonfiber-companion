<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Sdk\Api\Fields\DashboardField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SummaryIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What stopped moving, out of a `dashboard` envelope's payload.
 *
 * Its own reader beside {@see \Modules\Sdk\Api\Summaries}, which hands it the payload, because
 * the rows are a list of their own with their own refusals: a row this app
 * cannot read refuses the whole list rather than leaving it one row short,
 * which would be a queue drawn as better than it is.
 *
 * **Every field is refused rather than defaulted**, for {@see \Modules\Sdk\Api\Summaries}'
 * reason, except what blocked a row: the contract lets a service say nothing,
 * and absent and null both mean it did.
 */
final readonly class StoppedRows
{
    /** @param array<mixed> $data */
    public static function in(array $data): WhatStoppedMoving
    {
        if (! array_key_exists(DashboardField::Stuck->value, $data)) {
            throw SummaryIsUnreadable::noStopped();
        }

        $rows = $data[DashboardField::Stuck->value];

        if (! is_array($rows)) {
            throw SummaryIsUnreadable::noStopped();
        }

        $stopped = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw SummaryIsUnreadable::inStopped($position, DashboardField::Stuck);
            }

            $stopped[] = AStoppage::of(
                self::stall($row, $position),
                self::text($row, WireField::Name, $position),
                self::count($row, WireField::Items, $position),
                self::blocking($row, $position),
                self::count($row, DashboardField::HeldFor, $position),
            );
            $position++;
        }

        return WhatStoppedMoving::of(...$stopped);
    }

    /** @param array<mixed> $row */
    private static function stall(array $row, int $position): HowItStopped
    {
        $said = self::text($row, WireField::Stall, $position);

        return HowItStopped::tryFrom($said) ?? throw SummaryIsUnreadable::stall($said, $position);
    }

    /** @param array<mixed> $row */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw SummaryIsUnreadable::inStopped($position, $field);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw SummaryIsUnreadable::inStopped($position, $field);
        }

        return $said;
    }

    /** @param array<mixed> $row */
    private static function count(array $row, NamesAWireField $field, int $position): int
    {
        if (! array_key_exists($field->value, $row)) {
            throw SummaryIsUnreadable::inStopped($position, $field);
        }

        $count = $row[$field->value];

        if (! is_int($count)) {
            throw SummaryIsUnreadable::inStopped($position, $field);
        }

        return $count;
    }

    /**
     * What the service said was in the way, or nothing where it said nothing.
     *
     * @param array<mixed> $row
     */
    private static function blocking(array $row, int $position): string
    {
        if (! array_key_exists(DashboardField::Blocking->value, $row)) {
            return '';
        }

        $said = $row[DashboardField::Blocking->value];

        if ($said === null) {
            return '';
        }

        if (! is_string($said)) {
            throw SummaryIsUnreadable::inStopped($position, DashboardField::Blocking);
        }

        return $said;
    }
}
