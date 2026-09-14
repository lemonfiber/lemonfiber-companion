<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StatusEnvelope;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `status` envelope, as the listing an operator picks a verb from.
 *
 * The sibling of {@see Stoppages} for `N2-R7`'s payload, and written the same
 * way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **The overall condition is read before any row is.** `condition` is the
 * stack's own judgement over the services beside it, and it is not worked out
 * here from the rows — a second opinion assembled on a phone would disagree
 * with the machine the first time lemonfiber changed how it weighs a degraded
 * service, and the phone would be the one that was wrong.
 *
 * **The forms arrive whether or not anything in them is running.** A form with
 * everything stopped is exactly the form an operator opens the app to start, so
 * taking the list from the rows would hide the one form worth acting on.
 *
 * **A service that ended is built by a different constructor.** The wire may
 * carry `exit` and may not, and {@see Daemon::thatExited()} is what `C2` leaves
 * in place of a nullable seventh argument — so the decision *did this end with
 * a code* is made once, here, where the payload is in front of it.
 */
final readonly class Rosters
{
    /**
     * What the stack is running, in the order it listed it.
     *
     * @param Envelope<mixed> $envelope the `status` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Daemons
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw RosterIsUnreadable::missing(WireField::Data);
        }

        return Daemons::of(self::condition($data), self::forms($data), ...self::services($data));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Stoppages::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and an
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return StatusEnvelope::in($envelope)->data;
    }

    /**
     * What the stack says it all amounts to.
     *
     * @param array<mixed> $data
     */
    private static function condition(array $data): HowTheStackIsRunning
    {
        if (! array_key_exists(WireField::Condition->value, $data)) {
            throw RosterIsUnreadable::missing(WireField::Condition);
        }

        $said = $data[WireField::Condition->value];

        if (! is_string($said)) {
            throw RosterIsUnreadable::missing(WireField::Condition);
        }

        return HowTheStackIsRunning::tryFrom($said) ?? throw RosterIsUnreadable::condition($said);
    }

    /**
     * Every form the stack has, in the order it listed them.
     *
     * @param array<mixed> $data
     */
    private static function forms(array $data): Forms
    {
        $forms = [];
        $position = 0;

        foreach (self::listed($data, WireField::Forms) as $said) {
            if (! is_string($said) || trim($said) === '') {
                throw RosterIsUnreadable::form($position);
            }

            $forms[] = Form::called($said);
            $position++;
        }

        return $forms === [] ? Forms::none() : Forms::these(...$forms);
    }

    /**
     * Every service, refusing any row this app cannot show.
     *
     * @param  array<mixed> $data
     * @return list<Daemon>
     */
    private static function services(array $data): array
    {
        $daemons = [];
        $position = 0;

        foreach (self::listed($data, WireField::Services) as $row) {
            if (! is_array($row)) {
                throw RosterIsUnreadable::item($position);
            }

            $daemons[] = self::daemon($row, $position);
            $position++;
        }

        return $daemons;
    }

    /**
     * A named list, as it arrived.
     *
     * Returned with its keys, for {@see Stoppages::rows()}'s reason: both
     * callers walk it and count their own position, so a reindex here would be
     * a line nothing can observe.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function listed(array $data, WireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw RosterIsUnreadable::missing($field);
        }

        $listed = $data[$field->value];

        if (! is_array($listed)) {
            throw RosterIsUnreadable::missing($field);
        }

        return $listed;
    }

    /**
     * One service, by whichever of the two constructors its payload calls for.
     *
     * @param array<mixed> $row
     */
    private static function daemon(array $row, int $position): Daemon
    {
        $name = self::text($row, WireField::Name, $position);
        $id = ServiceId::called(self::text($row, WireField::Id, $position));
        $profile = Form::called(self::text($row, WireField::Profile, $position));
        $runs = self::runs($row, $position);
        $matters = self::matters($row, $position);
        $leaning = self::leaning($row, $position);

        $code = self::howItEnded($row, $position);

        if ($code === null) {
            return Daemon::called($name, $id, $profile, $runs, $matters, $leaning);
        }

        return Daemon::thatExited($name, $id, $profile, $runs, $matters, $leaning, $code);
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * Blank is refused here rather than left to {@see Daemon::called()} because
     * the position is only knowable here — the argument
     * {@see Stoppages::text()} makes about a listing of forty.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses
        // and {@see Stoppages::text()} explains.
        if (! array_key_exists($field->value, $row)) {
            throw RosterIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw RosterIsUnreadable::said($field, $position);
        }

        return $said;
    }

    /**
     * How one service is running, as a case rather than the word it arrived as.
     *
     * @param array<mixed> $row
     */
    private static function runs(array $row, int $position): HowAServiceRuns
    {
        $said = self::text($row, WireField::State, $position);

        return HowAServiceRuns::tryFrom($said) ?? throw RosterIsUnreadable::running($said, $position);
    }

    /**
     * How much one service matters, on the same terms.
     *
     * @param array<mixed> $row
     */
    private static function matters(array $row, int $position): HowMuchItMatters
    {
        $said = self::text($row, WireField::Criticality, $position);

        return HowMuchItMatters::tryFrom($said) ?? throw RosterIsUnreadable::matters($said, $position);
    }

    /**
     * What one service will not work without.
     *
     * @param array<mixed> $row
     */
    private static function leaning(array $row, int $position): WhatLeansOnIt
    {
        $leaning = [];

        foreach (self::listed($row, WireField::DependsOn) as $said) {
            if (! is_string($said) || trim($said) === '') {
                throw RosterIsUnreadable::leaning($position);
            }

            $leaning[] = ServiceId::called($said);
        }

        return $leaning === [] ? WhatLeansOnIt::nothing() : WhatLeansOnIt::these(...$leaning);
    }

    /**
     * What this service exited with, or nothing where it did not.
     *
     * Absent and null are both *it did not*, which is what the contract's
     * `exit?: int|null` says twice. Anything else is refused rather than read
     * as still running: the two look identical on a screen and one of them is a
     * service somebody needs to know about.
     *
     * One reading rather than a predicate beside a fetch. A method answering
     * *did it end* and a second answering *with what* would each guard the same
     * absence, and the second guard could never fire — an unreachable line that
     * reads as caution and no test can defend. `?int` here is the wire's own
     * optionality, and {@see Daemon::thatExited()} is where it stops being
     * optional.
     *
     * @param array<mixed> $row
     */
    private static function howItEnded(array $row, int $position): ?int
    {
        if (! array_key_exists(WireField::Exit->value, $row)) {
            return null;
        }

        $code = $row[WireField::Exit->value];

        if ($code === null) {
            return null;
        }

        if (! is_int($code)) {
            throw RosterIsUnreadable::ended($position);
        }

        return $code;
    }
}
