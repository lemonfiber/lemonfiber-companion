<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HostingEnvelope;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `hosting` envelope, as the listing this app can show.
 *
 * The sibling of {@see Stoppages} for what a machine keeps running, written the
 * same way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **What keeps the machine running is read before any row is.** It decides
 * which of the two listings this becomes, and a machine with no manager needs a
 * sentence the rows cannot supply. Reading the rows first and the manager after
 * would mean building a listing and then discovering it was the wrong kind.
 *
 * **The rows keep the stack's order.** Which order an operator should read them
 * in is a screen's decision, made where there is a screen to make it — the
 * argument {@see Reports} makes about findings.
 */
final readonly class Hosts
{
    /**
     * What a machine keeps running, in the order it listed.
     *
     * @param Envelope<mixed> $envelope the `hosting` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatRunsUnattended
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HostingIsUnreadable::missing(WireField::Data);
        }

        $keeper = self::keeper($data);
        $commands = self::commands($data);

        return $keeper->configuresAnything()
            ? WhatRunsUnattended::keptBy($keeper, ...$commands)
            : WhatRunsUnattended::unsupported(self::instruction($data), ...$commands);
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
        return HostingEnvelope::in($envelope)->data;
    }

    /**
     * What keeps this machine's commands running.
     *
     * Refused rather than defaulted where it is absent or a word this app does
     * not read. The contract's own default is `unsupported` and it says why —
     * a machine nobody has told is a machine nothing is known about — but that
     * is the *stack's* default to apply, and this app applying it too would
     * turn a field that failed to arrive into a platform claim.
     *
     * @param array<mixed> $data
     */
    private static function keeper(array $data): WhatKeepsItRunning
    {
        if (! array_key_exists(WireField::Manager->value, $data)) {
            throw HostingIsUnreadable::missing(WireField::Manager);
        }

        $said = $data[WireField::Manager->value];

        if (! is_string($said)) {
            throw HostingIsUnreadable::missing(WireField::Manager);
        }

        return WhatKeepsItRunning::tryFrom($said) ?? throw HostingIsUnreadable::manager($said);
    }

    /**
     * What to do where this platform configures nothing.
     *
     * Read only on that arm, and required there. Everywhere else the wire
     * carries nothing here and there is nothing to carry.
     *
     * @param array<mixed> $data
     */
    private static function instruction(array $data): string
    {
        if (! array_key_exists(WireField::Instruction->value, $data)) {
            throw HostingIsUnreadable::withNothingToDoInstead();
        }

        $said = $data[WireField::Instruction->value];

        if (! is_string($said) || trim($said) === '') {
            throw HostingIsUnreadable::withNothingToDoInstead();
        }

        return $said;
    }

    /**
     * Every long-running command, refusing any row this app cannot show.
     *
     * @param  array<mixed> $data
     * @return list<Unattended>
     */
    private static function commands(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data) as $row) {
            if (! is_array($row)) {
                throw HostingIsUnreadable::command($position);
            }

            $found[] = self::one($row, $position);
            $position++;
        }

        return $found;
    }

    /**
     * One row, as the command it describes.
     *
     * The orphan arm is taken from the standing rather than from the presence
     * of `missing`, which is what keeps the two in step: a row saying it is
     * orphaned and naming no program is refused here, and one naming a program
     * at any other standing has that word dropped rather than carried into a
     * type that cannot hold it.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row, int $position): Unattended
    {
        $standing = self::standing($row, $position);
        $name = self::text($row, WireField::Name, $position);
        $command = self::text($row, WireField::Command, $position);
        $guarantees = self::text($row, WireField::Guarantees, $position);

        if ($standing !== HowItIsHosted::Orphaned) {
            return Unattended::called($name, $command, $guarantees, $standing);
        }

        return Unattended::orphaned(
            $name,
            $command,
            $guarantees,
            self::text($row, WireField::Missing, $position),
        );
    }

    /**
     * The rows, as they arrived.
     *
     * Returned with their keys, for {@see Households::rows()}'s reason: the
     * only caller walks them and counts its own position, so a reindex here
     * would be a line nothing can observe.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Commands->value, $data)) {
            throw HostingIsUnreadable::missing(WireField::Commands);
        }

        $rows = $data[WireField::Commands->value];

        if (! is_array($rows)) {
            throw HostingIsUnreadable::missing(WireField::Commands);
        }

        return $rows;
    }

    /**
     * Where one command stands, as a word this app reads.
     *
     * @param array<mixed> $row
     */
    private static function standing(array $row, int $position): HowItIsHosted
    {
        $said = self::text($row, WireField::Standing, $position);

        return HowItIsHosted::tryFrom($said) ?? throw HostingIsUnreadable::standing($said, $position);
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * Blank is refused here rather than left to {@see Unattended::called()}
     * because the position is only knowable here: the refusal that says
     * *command 4 says nothing about what it guarantees* can be acted on, and
     * the one that says *a command says nothing* leaves somebody reading a
     * listing of nine looking for it.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses: a
        // row that carries the key and a row that does not are the same refusal
        // here, and the coalesce hides which one arrived from anybody reading
        // the line.
        if (! array_key_exists($field->value, $row)) {
            throw HostingIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw HostingIsUnreadable::said($field, $position);
        }

        return $said;
    }
}
