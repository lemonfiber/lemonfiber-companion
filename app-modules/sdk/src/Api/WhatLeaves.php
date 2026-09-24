<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\OutboundEnvelope;
use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Internal\Attributions;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `outbound` envelope, as what leaves a machine.
 *
 * The sibling of {@see Origins}, written the same way: a static fold with no
 * state, reading through {@see WireField}, and refusing rather than
 * salvaging, by position, anything the kernel would refuse.
 *
 * **The two lists are read apart and stay apart.** `ours` becomes
 * {@see ARequestOfOurs} and `theirs` becomes {@see ARequestOfTheirs}, and
 * nothing here builds a list of both.
 *
 * **An unrecorded service's destination and purpose are not read.** The stack
 * fills them with words of its own where it has no record, and `recorded` is
 * the field that says so — so a row with `recorded: false` becomes the
 * unrecorded arm whatever else it carries, and the screen writes its own
 * sentence for *nobody knows*. Who put the service there is read on both
 * arms.
 */
final readonly class WhatLeaves
{
    /**
     * Everything that leaves a machine, in two lists.
     *
     * @param Envelope<mixed> $envelope the `outbound` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatLeavesThisMachine
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw OutboundIsUnreadable::missing(WireField::Data);
        }

        return WhatLeavesThisMachine::of(OurRequests::of(...self::ours($data)), TheirRequests::of(...self::theirs($data)));
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
        return OutboundEnvelope::in($envelope)->data;
    }

    /**
     * Every request lemonfiber makes on its own account.
     *
     * @param  array<mixed>         $data
     * @return list<ARequestOfOurs>
     */
    private static function ours(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, WireField::Ours) as $row) {
            if (! is_array($row)) {
                throw OutboundIsUnreadable::row(WireField::Ours, $position);
            }

            $found[] = ARequestOfOurs::described(
                self::asksFor($row, $position),
                self::destinations($row, $position),
                self::text($row, WireField::Ours, WireField::Purpose, $position),
                self::text($row, WireField::Ours, WireField::Sends, $position),
                WhetherItIsAllowed::said(allowed: self::flag($row, WireField::Ours, WireField::Allowed, $position)),
                self::text($row, WireField::Ours, WireField::Switch, $position),
                self::text($row, WireField::Ours, WireField::Cost, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What each of the stack's services reaches.
     *
     * @param  array<mixed>           $data
     * @return list<ARequestOfTheirs>
     */
    private static function theirs(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, WireField::Theirs) as $row) {
            if (! is_array($row)) {
                throw OutboundIsUnreadable::row(WireField::Theirs, $position);
            }

            $found[] = self::oneOfTheirs($row, $position);
            $position++;
        }

        return $found;
    }

    /**
     * One service's row, on the arm `recorded` names.
     *
     * @param array<mixed> $row
     */
    private static function oneOfTheirs(array $row, int $position): ARequestOfTheirs
    {
        $service = ServiceId::called(self::text($row, WireField::Theirs, WireField::Service, $position));
        $origin = self::origin($row, $position);

        if (! self::flag($row, WireField::Theirs, WireField::Recorded, $position)) {
            return ARequestOfTheirs::unrecorded($service, $origin);
        }

        return ARequestOfTheirs::recorded(
            $service,
            self::destination($row, $position),
            self::text($row, WireField::Theirs, WireField::Purpose, $position),
            $origin,
        );
    }

    /**
     * Who put the row's service on the stack, read on both arms.
     *
     * An unrecorded row's destination and purpose are the stack's placeholders
     * and are not read; its origin is not a placeholder, and a plugin's service
     * is the likeliest row to be unrecorded — so skipping it there would drop
     * the attribution exactly where it is most needed.
     *
     * @param array<mixed> $row
     */
    private static function origin(array $row, int $position): WhoPutItThere
    {
        try {
            return Attributions::of($row);
        } catch (OriginIsUnreadable $why) {
            throw OutboundIsUnreadable::origin($position, $why);
        }
    }

    /**
     * One list's rows, as they arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, WireField $list): array
    {
        if (! array_key_exists($list->value, $data)) {
            throw OutboundIsUnreadable::missing($list);
        }

        $rows = $data[$list->value];

        if (! is_array($rows)) {
            throw OutboundIsUnreadable::missing($list);
        }

        return $rows;
    }

    /**
     * Which of lemonfiber's requests a row is.
     *
     * @param array<mixed> $row
     */
    private static function asksFor(array $row, int $position): WhatLemonfiberAsksFor
    {
        $said = self::text($row, WireField::Ours, WireField::Reach, $position);

        return WhatLemonfiberAsksFor::tryFrom($said) ?? throw OutboundIsUnreadable::reach($said, $position);
    }

    /**
     * Where one of lemonfiber's requests goes; empty where nothing is configured.
     *
     * @param array<mixed> $row
     */
    private static function destinations(array $row, int $position): WhereItGoes
    {
        if (! array_key_exists(WireField::Destination->value, $row) || ! is_array($row[WireField::Destination->value])) {
            throw OutboundIsUnreadable::said(WireField::Ours, WireField::Destination, $position);
        }

        $found = [];

        foreach ($row[WireField::Destination->value] as $one) {
            if (! is_string($one) || trim($one) === '') {
                throw OutboundIsUnreadable::said(WireField::Ours, WireField::Destination, $position);
            }

            $found[] = $one;
        }

        return WhereItGoes::to(...$found);
    }

    /**
     * Where a recorded service goes, where empty is an answer: it reaches nothing.
     *
     * @param array<mixed> $row
     */
    private static function destination(array $row, int $position): string
    {
        if (! array_key_exists(WireField::Destination->value, $row) || ! is_string($row[WireField::Destination->value])) {
            throw OutboundIsUnreadable::said(WireField::Theirs, WireField::Destination, $position);
        }

        return $row[WireField::Destination->value];
    }

    /**
     * A yes-or-no field of one row.
     *
     * @param array<mixed> $row
     */
    private static function flag(array $row, WireField $list, WireField $field, int $position): bool
    {
        if (! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw OutboundIsUnreadable::said($list, $field, $position);
        }

        return $row[$field->value];
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $list, WireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $row)) {
            throw OutboundIsUnreadable::said($list, $field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw OutboundIsUnreadable::said($list, $field, $position);
        }

        return $said;
    }
}
