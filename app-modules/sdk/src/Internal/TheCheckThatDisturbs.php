<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\WhetherCheckingDisturbs;
use Modules\Sdk\Api\Fields\DoctorField;
use Modules\Sdk\Api\ReportIsUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * Reads one finding's `disruptive`: what checking it disturbs, the check a diagnosis runs it under, and its bound.
 *
 * Absent or `null` is a finding that waits on no such check. Anything else the
 * contract would not send refuses the report, naming the finding, for
 * {@see ReportIsUnreadable::inFinding()}'s reason.
 */
final readonly class TheCheckThatDisturbs
{
    /** @param array<mixed> $row one finding, as the report carried it */
    public static function in(array $row, int $position): WhetherCheckingDisturbs
    {
        if (! array_key_exists(DoctorField::Disruptive->value, $row) || $row[DoctorField::Disruptive->value] === null) {
            return WhetherCheckingDisturbs::not();
        }

        $disruptive = $row[DoctorField::Disruptive->value];

        if (! is_array($disruptive)) {
            throw ReportIsUnreadable::inFinding($position, DoctorField::Disruptive);
        }

        $disturbs = self::word($disruptive, WireField::Disturbs, $position);
        $only = self::word($disruptive, DoctorField::Only, $position);
        if (! array_key_exists(DoctorField::Bounded->value, $disruptive) || $disruptive[DoctorField::Bounded->value] === null) {
            return WhetherCheckingDisturbs::so(ACheckThatDisturbs::unbounded($disturbs, $only));
        }

        $bounded = $disruptive[DoctorField::Bounded->value];

        if (! is_int($bounded) || $bounded < 0) {
            throw ReportIsUnreadable::inFinding($position, DoctorField::Bounded);
        }

        return WhetherCheckingDisturbs::so(ACheckThatDisturbs::boundedTo($disturbs, $only, $bounded));
    }

    /** @param array<mixed> $disruptive */
    private static function word(array $disruptive, DoctorField|WireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $disruptive) || ! is_string($disruptive[$field->value]) || trim($disruptive[$field->value]) === '') {
            throw ReportIsUnreadable::inFinding($position, $field);
        }

        return $disruptive[$field->value];
    }
}
