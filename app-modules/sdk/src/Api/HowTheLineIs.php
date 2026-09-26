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
use Lemonfiber\Sdk\Generated\BandwidthEnvelope;
use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Remark;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;
use Modules\Sdk\Api\Fields\BandwidthField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `bandwidth` envelope, as how a stack shares its line.
 *
 * The sibling of {@see WhatIsTold}, written the same way: a static fold with
 * no state, reading through {@see WireField}, refusing rather than defaulting
 * anything the kernel would refuse.
 *
 * **Absent and `null` are one answer for the optional parts**, and each lands
 * on its own arm: no capacity is *nothing measured it*, no cap is *none was
 * declared* — never a cap of zero, which arrives as a number. Where the month
 * stands is read only beside a cap, and a standing with no cap to stand against
 * is refused as a reading that contradicts itself.
 *
 * **What this app does not read is recorded in
 * `WhatTheContractCarriesThatNothingReadsTest`**: each client's holding, the
 * month's metering, the override, the household's hours and the structured
 * limits the `says` sentences already carry.
 */
final readonly class HowTheLineIs
{
    /**
     * How a stack shares its line, with whatever it knows about it.
     *
     * @param Envelope<mixed> $envelope the `bandwidth` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): HowTheLineIsShared
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw BandwidthIsUnreadable::missing(WireField::Data->value);
        }

        $line = HowTheLineIsShared::standing(
            self::restraint($data),
            self::text($data, WireField::Means->value, WireField::Means),
            self::says($data, BandwidthField::Down),
            self::says($data, BandwidthField::Up),
            self::remarks($data, BandwidthField::Cautions),
            self::remarks($data, BandwidthField::Untouched),
        );

        return self::optionals($line, $data);
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
        return BandwidthEnvelope::in($envelope)->data;
    }

    /**
     * The parts the stack may not know, each added where it does.
     *
     * @param array<mixed> $data
     */
    private static function optionals(HowTheLineIsShared $line, array $data): HowTheLineIsShared
    {
        if (self::carries($data, BandwidthField::Capacity)) {
            $line = $line->measuredAt(self::capacity(self::object($data, BandwidthField::Capacity)));
        }

        if (! self::carries($data, BandwidthField::Cap) && self::carries($data, BandwidthField::Reached)) {
            throw BandwidthIsUnreadable::missing(BandwidthField::Cap->value);
        }

        if (self::carries($data, BandwidthField::Cap)) {
            $line = $line->cappedAt(self::cap($data));
        }

        if (self::carries($data, BandwidthField::Acting)) {
            $line = $line->withASpentCapDoing(Remark::said(self::text($data, BandwidthField::Acting->value, BandwidthField::Acting), BandwidthField::Acting->value));
        }

        if (self::carries($data, WireField::Ratio)) {
            return $line->withUploadCosting(Remark::said(self::text($data, WireField::Ratio->value, WireField::Ratio), WireField::Ratio->value));
        }

        return $line;
    }

    /**
     * Where the line stands, as a word this app reads.
     *
     * @param array<mixed> $data
     */
    private static function restraint(array $data): WhereTheLineStands
    {
        $said = self::text($data, BandwidthField::Restraint->value, BandwidthField::Restraint);

        return WhereTheLineStands::tryFrom($said)
            ?? throw BandwidthIsUnreadable::word(BandwidthField::Restraint->value, $said, ...array_map(static fn(WhereTheLineStands $case): string => $case->value, WhereTheLineStands::cases()));
    }

    /**
     * One direction's limit, in the stack's one sentence.
     *
     * @param array<mixed> $data
     */
    private static function says(array $data, NamesAWireField $direction): string
    {
        return self::text(self::object($data, $direction), BandwidthField::Says->under($direction), BandwidthField::Says);
    }

    /**
     * A list of the stack's sentences.
     *
     * @param array<mixed> $data
     */
    private static function remarks(array $data, NamesAWireField $list): Remarks
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw BandwidthIsUnreadable::missing($list->value);
        }

        $said = [];
        $position = 0;

        foreach ($data[$list->value] as $one) {
            if (! is_string($one) || trim($one) === '') {
                throw BandwidthIsUnreadable::remark($list->value, $position);
            }

            $said[] = $one;
            $position++;
        }

        return Remarks::of(...$said);
    }

    /**
     * What the line was measured to carry.
     *
     * @param array<mixed> $capacity
     */
    private static function capacity(array $capacity): WhatTheLineCarries
    {
        $source = self::text($capacity, BandwidthField::Source->under(BandwidthField::Capacity), BandwidthField::Source);

        return WhatTheLineCarries::measured(
            self::count($capacity, BandwidthField::Down, BandwidthField::Capacity),
            self::count($capacity, BandwidthField::Up, BandwidthField::Capacity),
            HowTheLineWasMeasured::tryFrom($source)
                ?? throw BandwidthIsUnreadable::word(BandwidthField::Source->under(BandwidthField::Capacity), $source, ...array_map(static fn(HowTheLineWasMeasured $case): string => $case->value, HowTheLineWasMeasured::cases())),
            Instant::atEpochSeconds(self::count($capacity, WireField::Taken, BandwidthField::Capacity)),
            WhetherItGoesThroughTheTunnel::said(throughTunnel: self::flag($capacity, BandwidthField::ThroughTunnel, BandwidthField::Capacity)),
        );
    }

    /**
     * The monthly cap, with where the month stands where the stack counted it.
     *
     * @param array<mixed> $data
     */
    private static function cap(array $data): AMonthlyCap
    {
        $cap = self::object($data, BandwidthField::Cap);
        $exceeded = self::text($cap, BandwidthField::Exceeded->under(BandwidthField::Cap), BandwidthField::Exceeded);
        $monthly = AMonthlyCap::of(
            self::count($cap, BandwidthField::Monthly, BandwidthField::Cap),
            WhatACapDoes::tryFrom($exceeded)
                ?? throw BandwidthIsUnreadable::word(BandwidthField::Exceeded->under(BandwidthField::Cap), $exceeded, ...array_map(static fn(WhatACapDoes $case): string => $case->value, WhatACapDoes::cases())),
        );

        if (! self::carries($data, BandwidthField::Reached)) {
            return $monthly;
        }

        $reached = self::text($data, BandwidthField::Reached->value, BandwidthField::Reached);

        return $monthly->standing(
            WhereTheMonthStands::tryFrom($reached)
                ?? throw BandwidthIsUnreadable::word(BandwidthField::Reached->value, $reached, ...array_map(static fn(WhereTheMonthStands $case): string => $case->value, WhereTheMonthStands::cases())),
        );
    }

    /**
     * A nested object, required where it is asked for.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function object(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw BandwidthIsUnreadable::missing($field->value);
        }

        return $data[$field->value];
    }

    /**
     * A count that cannot be below zero.
     *
     * @param array<mixed> $data
     */
    private static function count(array $data, NamesAWireField $field, NamesAWireField $parent): int
    {
        if (! array_key_exists($field->value, $data) || ! is_int($data[$field->value]) || $data[$field->value] < 0) {
            throw BandwidthIsUnreadable::missing($field->under($parent));
        }

        return $data[$field->value];
    }

    /**
     * A yes-or-no field.
     *
     * @param array<mixed> $data
     */
    private static function flag(array $data, NamesAWireField $field, NamesAWireField $parent): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw BandwidthIsUnreadable::missing($field->under($parent));
        }

        return $data[$field->value];
    }

    /**
     * Whether the payload says anything under an optional field.
     *
     * Absent and `null` are the same answer, for {@see Records::carries()}'s
     * reason.
     *
     * @param array<mixed> $data
     */
    private static function carries(array $data, NamesAWireField $field): bool
    {
        return array_key_exists($field->value, $data) && $data[$field->value] !== null;
    }

    /**
     * A named field, as text an operator can be shown.
     *
     * `$where` is how the refusal names it, which is the path for a nested one.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, string $where, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $data)) {
            throw BandwidthIsUnreadable::missing($where);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw BandwidthIsUnreadable::missing($where);
        }

        return $said;
    }
}
