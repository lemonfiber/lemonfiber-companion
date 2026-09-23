<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function filter_var;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HistoryEnvelope;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereItStopsShort;
use Modules\Sdk\Internal\Wire;

use function preg_match;
use function trim;

/**
 * The `history` envelope, as the record this app can show.
 *
 * The sibling of {@see Hosts} for what a stack changed, written the same way:
 * a static fold with no state, reading through {@see WireField} so no field
 * name is spelled twice, and refusing rather than salvaging.
 *
 * **Everything the types one layer down would refuse is refused here first**,
 * as {@see HistoryIsUnreadable}, with the row's position. The kernel's own
 * refusals are the backstop rather than the reading: the adapter turns this
 * class's refusal into an obstacle, and a blank that reached a kernel
 * constructor would surface as an uncaught raise on a screen instead.
 *
 * **The horizon is read before any change.** It is what makes the last row of
 * the record mean *the end of what is kept* rather than *the machine's first
 * day*, and a record assembled first and then found to have none would be a
 * listing built for an answer that does not exist.
 *
 * **The rows keep the stack's order**, which is newest first and, for two made
 * at one instant, whichever order the stack gave. Re-deciding it here would be
 * an opinion about which of two simultaneous changes came first.
 */
final readonly class Records
{
    /**
     * What a stack changed, newest first, and how far back that goes.
     *
     * @param Envelope<mixed> $envelope the `history` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheRecord
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HistoryIsUnreadable::missing(WireField::Data);
        }

        return TheRecord::reaching(self::horizon($data), ...self::changes($data));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Households::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and an
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return HistoryEnvelope::in($envelope)->data;
    }

    /**
     * How far back the record goes, required and never blank.
     *
     * @param array<mixed> $data
     */
    private static function horizon(array $data): string
    {
        if (! array_key_exists(WireField::Horizon->value, $data)) {
            throw HistoryIsUnreadable::missing(WireField::Horizon);
        }

        $said = $data[WireField::Horizon->value];

        if (! is_string($said) || trim($said) === '') {
            throw HistoryIsUnreadable::missing(WireField::Horizon);
        }

        return $said;
    }

    /**
     * Every change, refusing any row this app cannot show.
     *
     * @param  array<mixed> $data
     * @return list<Change>
     */
    private static function changes(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data) as $row) {
            if (! is_array($row)) {
                throw HistoryIsUnreadable::change($position);
            }

            $found[] = self::one($row, $position);
            $position++;
        }

        return $found;
    }

    /**
     * The rows, as they arrived.
     *
     * Returned with their keys, for {@see Hosts::rows()}'s reason: the only
     * caller walks them and counts its own position.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Changes->value, $data)) {
            throw HistoryIsUnreadable::missing(WireField::Changes);
        }

        $rows = $data[WireField::Changes->value];

        if (! is_array($rows)) {
            throw HistoryIsUnreadable::missing(WireField::Changes);
        }

        return $rows;
    }

    /**
     * One row, as the change it describes.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row, int $position): Change
    {
        $change = Change::made(
            self::text($row, WireField::Did, $position),
            self::text($row, WireField::Operation, $position),
            self::text($row, WireField::Target, $position),
            self::when($row, $position),
            self::reversal($row, $position),
            self::alongside($row, $position),
        );

        return self::shortfall($change, $row, $position);
    }

    /**
     * When one change was made, or that the stack's clock would not say.
     *
     * Digits only, and a number that fits: the contract describes the field as
     * seconds since the epoch written as a string. The pattern is checked
     * before the conversion because the conversion alone is forgiving — it
     * would take a sign or surrounding space — and a stamp the core never
     * writes is one this app should not trust to put a record in order.
     *
     * **Zero is not a moment.** It is what the stack writes where its clock
     * would not answer, so it is read as {@see WhenItWasMade::unreadable()}
     * rather than as the first second of 1970 — a date nobody observed, which
     * the screen would otherwise draw with confidence.
     *
     * @param array<mixed> $row
     */
    private static function when(array $row, int $position): WhenItWasMade
    {
        $said = self::text($row, WireField::At, $position);
        $seconds = filter_var($said, FILTER_VALIDATE_INT);

        if (preg_match('/^\d+$/', $said) !== 1 || $seconds === false) {
            throw HistoryIsUnreadable::at($said, $position);
        }

        return $seconds === 0
            ? WhenItWasMade::unreadable()
            : WhenItWasMade::at(Instant::atEpochSeconds($seconds));
    }

    /**
     * How far one change goes back, as a word this app reads.
     *
     * @param array<mixed> $row
     */
    private static function reversal(array $row, int $position): HowFarItGoesBack
    {
        $said = self::text($row, WireField::Reversal, $position);

        return HowFarItGoesBack::tryFrom($said) ?? throw HistoryIsUnreadable::reversal($said, $position);
    }

    /**
     * How many changes came with this one, which includes it.
     *
     * @param array<mixed> $row
     */
    private static function alongside(array $row, int $position): int
    {
        if (! array_key_exists(WireField::Alongside->value, $row)) {
            throw HistoryIsUnreadable::alongside($position);
        }

        $said = $row[WireField::Alongside->value];

        if (! is_int($said) || $said < 1) {
            throw HistoryIsUnreadable::alongside($position);
        }

        return $said;
    }

    /**
     * The change, carrying where putting it back stops short, where the row says.
     *
     * A suggestion with no reason is refused rather than dropped or kept. The
     * stack builds both from one refusal to go further, which always has a
     * reason — so a row carrying only the suggestion did not come from that,
     * and {@see WhereItStopsShort} cannot hold it.
     *
     * @param array<mixed> $row
     */
    private static function shortfall(Change $change, array $row, int $position): Change
    {
        if (self::carries($row, WireField::Because)) {
            return $change->stoppingShort(self::whereItStopsShort($row, $position));
        }

        if (self::carries($row, WireField::Instead)) {
            throw HistoryIsUnreadable::insteadWithoutReason($position);
        }

        return $change;
    }

    /**
     * Why putting a change back stops short, and what to do instead if the row says.
     *
     * @param array<mixed> $row
     */
    private static function whereItStopsShort(array $row, int $position): WhereItStopsShort
    {
        $why = self::text($row, WireField::Because, $position);

        if (! self::carries($row, WireField::Instead)) {
            return WhereItStopsShort::because($why);
        }

        return WhereItStopsShort::suggesting($why, self::text($row, WireField::Instead, $position));
    }

    /**
     * Whether a row says anything under an optional field.
     *
     * Absent and `null` are the same answer — the contract describes both
     * fields as a string or null — and anything else is read, so a blank or a
     * number there is refused by {@see self::text()} rather than taken as
     * silence.
     *
     * @param array<mixed> $row
     */
    private static function carries(array $row, WireField $field): bool
    {
        return array_key_exists($field->value, $row) && $row[$field->value] !== null;
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses:
        // a row that carries the key and one that does not are the same
        // refusal here, and the coalesce hides which one arrived.
        if (! array_key_exists($field->value, $row)) {
            throw HistoryIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw HistoryIsUnreadable::said($field, $position);
        }

        return $said;
    }
}
