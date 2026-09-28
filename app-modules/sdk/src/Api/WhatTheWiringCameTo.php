<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SeedEnvelope;
use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\HowSeriousAConnectionIs;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Sdk\Api\Fields\SeedField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `seed` envelope into what one wiring run came to.
 *
 * Written the way {@see WhatIsAlreadyHere} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see SeedIsUnreadable}.
 * Every connection keeps the stack's order.
 *
 * **Each state is read with what it carries**, and a word outside the
 * thirteen is refused rather than read as the nearest: a failure read as wired
 * is the one mistake a wiring report exists to prevent.
 */
final readonly class WhatTheWiringCameTo
{
    /**
     * What the run came to.
     *
     * @param Envelope<mixed> $envelope the `seed` envelope, as the finished work answered with it
     */
    public static function in(Envelope $envelope): TheWiring
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SeedIsUnreadable::missing(WireField::Data);
        }

        $said = Required::text($data, SeedField::Assessment, SeedIsUnreadable::missing(SeedField::Assessment));
        $judged = HowDriftWasJudged::tryFrom($said) ?? throw SeedIsUnreadable::missing(SeedField::Assessment);
        $rehearsed = Required::flag($data, WireField::Rehearsed, SeedIsUnreadable::missing(WireField::Rehearsed));
        $unsupported = WhatIsUnsupported::these(...self::unsupported($data));
        $connections = self::wirings($data);

        return $rehearsed
            ? TheWiring::rehearsed($judged, $unsupported, ...$connections)
            : TheWiring::written($judged, $unsupported, ...$connections);
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
        return SeedEnvelope::in($envelope)->data;
    }

    /**
     * Every connection the run attempted, in the stack's order.
     *
     * @param  array<mixed>      $data
     * @return list<AConnection>
     */
    private static function wirings(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, SeedField::Wirings, SeedIsUnreadable::missing(SeedField::Wirings)) as $row) {
            if (! is_array($row)) {
                throw SeedIsUnreadable::entry(SeedField::Wirings, $position, SeedField::Connection);
            }

            $found[] = AConnection::of(
                Required::text($row, SeedField::Connection, SeedIsUnreadable::entry(SeedField::Wirings, $position, SeedField::Connection)),
                self::breaks(self::table($row, WireField::Severity, $position), $position),
                self::ended(self::table($row, WireField::State, $position), $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * How serious one connection's outcome is; a warning with what breaks and what would put it right.
     *
     * @param array<mixed> $severity
     */
    private static function breaks(array $severity, int $position): WhatItWouldBreak
    {
        $refused = SeedIsUnreadable::entry(SeedField::Wirings, $position, WireField::Severity);
        $said = HowSeriousAConnectionIs::tryFrom(Required::text($severity, WireField::Severity, $refused)) ?? throw $refused;

        return match ($said) {
            HowSeriousAConnectionIs::Informational => WhatItWouldBreak::nothing(),
            HowSeriousAConnectionIs::Warning => WhatItWouldBreak::warning(
                Required::text($severity, SeedField::Breakage, SeedIsUnreadable::entry(SeedField::Wirings, $position, SeedField::Breakage)),
                Required::text($severity, SeedField::Remediation, SeedIsUnreadable::entry(SeedField::Wirings, $position, SeedField::Remediation)),
            ),
        };
    }

    /**
     * How one connection turned out, with what its state carries.
     *
     * @param array<mixed> $state
     */
    private static function ended(array $state, int $position): HowAConnectionEnded
    {
        $refused = SeedIsUnreadable::entry(SeedField::Wirings, $position, WireField::State);
        $said = WhereAConnectionStands::tryFrom(Required::text($state, WireField::State, $refused)) ?? throw $refused;

        return match ($said) {
            WhereAConnectionStands::Conflicted => HowAConnectionEnded::conflicted(
                Required::text($state, WireField::Ours, SeedIsUnreadable::entry(SeedField::Wirings, $position, WireField::Ours)),
                self::optional($state, SeedField::Yours, $position),
            ),
            WhereAConnectionStands::WouldWire => HowAConnectionEnded::wouldWire(
                self::optional($state, WireField::Ours, $position),
                self::optional($state, SeedField::Yours, $position),
            ),
            WhereAConnectionStands::Observed,
            WhereAConnectionStands::Skipped,
            WhereAConnectionStands::Refused => HowAConnectionEnded::because(
                $said,
                Required::text($state, WireField::Reason, SeedIsUnreadable::entry(SeedField::Wirings, $position, WireField::Reason)),
            ),
            WhereAConnectionStands::Failed => HowAConnectionEnded::failed(
                Required::text($state, WireField::Detail, SeedIsUnreadable::entry(SeedField::Wirings, $position, WireField::Detail)),
            ),
            WhereAConnectionStands::Wired,
            WhereAConnectionStands::AlreadyWired,
            WhereAConnectionStands::Drifted,
            WhereAConnectionStands::Stale,
            WhereAConnectionStands::Adopted,
            WhereAConnectionStands::Unmanaged,
            WhereAConnectionStands::WouldAdopt => HowAConnectionEnded::plainly($said),
        };
    }

    /**
     * A table one connection carries, refused where it is not one.
     *
     * @param  array<mixed> $row
     * @return array<mixed>
     */
    private static function table(array $row, NamesAWireField $field, int $position): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value])) {
            throw SeedIsUnreadable::entry(SeedField::Wirings, $position, $field);
        }

        return $row[$field->value];
    }

    /**
     * A value the stack may leave out, as `''` where it did.
     *
     * Absent and null are the stack not saying, which the contract allows. A
     * value that is there and is not text with something in it is refused
     * rather than read as either.
     *
     * @param array<mixed> $state
     */
    private static function optional(array $state, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $state) || $state[$field->value] === null) {
            return '';
        }

        $said = $state[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw SeedIsUnreadable::entry(SeedField::Wirings, $position, $field);
        }

        return $said;
    }

    /**
     * What the run could not wire because it cannot speak to it, each with why; none where the stack lists none.
     *
     * @param  array<mixed>      $data
     * @return list<Unsupported>
     */
    private static function unsupported(array $data): array
    {
        if (! array_key_exists(WireField::Unsupported->value, $data)) {
            return [];
        }

        $found = [];
        $position = 0;

        foreach (Required::rows($data, WireField::Unsupported, SeedIsUnreadable::missing(WireField::Unsupported)) as $row) {
            if (! is_array($row)) {
                throw SeedIsUnreadable::entry(WireField::Unsupported, $position, WireField::What);
            }

            $found[] = Unsupported::of(
                Required::text($row, WireField::What, SeedIsUnreadable::entry(WireField::Unsupported, $position, WireField::What)),
                Required::text($row, WireField::Because, SeedIsUnreadable::entry(WireField::Unsupported, $position, WireField::Because)),
            );
            $position++;
        }

        return $found;
    }
}
