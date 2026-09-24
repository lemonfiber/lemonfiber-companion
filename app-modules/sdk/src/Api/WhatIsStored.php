<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StoredEnvelope;
use Modules\Kernel\Api\SomethingBeside;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `stored` envelope, as what a stack keeps on its machine.
 *
 * The sibling of {@see WhatLeaves}, written the same way: a static fold with no
 * state, reading through {@see WireField}, refusing by list and position
 * anything the kernel would refuse.
 *
 * **Three lists, read apart and kept apart.** What is kept and what sits
 * beside it answer different questions, and the second is the one somebody
 * looks for — their library — so nothing here builds a list of both.
 *
 * **`removal` is not read.** A listing is always a run that removed nothing;
 * what taking lemonfiber off a machine left behind belongs to the surface that
 * takes it off, and is recorded as unread with that reason.
 */
final readonly class WhatIsStored
{
    /**
     * Everything a stack keeps, where, and why.
     *
     * @param Envelope<mixed> $envelope the `stored` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatThisMachineKeeps
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StoredIsUnreadable::missing(WireField::Data);
        }

        return WhatThisMachineKeeps::of(
            TheRoots::of(...self::roots($data)),
            WhatIsKept::of(...self::kept($data)),
            WhatIsBeside::of(...self::beside($data)),
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
        return StoredEnvelope::in($envelope)->data;
    }

    /**
     * The directories it all sits under.
     *
     * @param  array<mixed>             $data
     * @return list<WhereThingsAreKept>
     */
    private static function roots(array $data): array
    {
        $found = [];

        $position = 0;

        foreach (self::rows($data, WireField::Roots) as $row) {
            if (! is_array($row)) {
                throw StoredIsUnreadable::row(WireField::Roots, $position);
            }

            $found[] = WhereThingsAreKept::at(
                self::text($row, WireField::Roots, WireField::At, $position),
                self::text($row, WireField::Roots, WireField::What, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Each thing kept.
     *
     * @param  array<mixed>        $data
     * @return list<SomethingKept>
     */
    private static function kept(array $data): array
    {
        $found = [];

        $position = 0;

        foreach (self::rows($data, WireField::Kept) as $row) {
            if (! is_array($row)) {
                throw StoredIsUnreadable::row(WireField::Kept, $position);
            }

            $found[] = SomethingKept::kept(
                self::text($row, WireField::Kept, WireField::What, $position),
                self::text($row, WireField::Kept, WireField::At, $position),
                self::text($row, WireField::Kept, WireField::Why, $position),
                WhetherItHoldsASecret::said(secret: self::flag($row, WireField::Kept, WireField::Secret, $position)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What is here and is not the stack's.
     *
     * @param  array<mixed>          $data
     * @return list<SomethingBeside>
     */
    private static function beside(array $data): array
    {
        $found = [];

        $position = 0;

        foreach (self::rows($data, WireField::Beside) as $row) {
            if (! is_array($row)) {
                throw StoredIsUnreadable::row(WireField::Beside, $position);
            }

            $found[] = SomethingBeside::named(
                self::text($row, WireField::Beside, WireField::What, $position),
                self::text($row, WireField::Beside, WireField::Why, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * One list, required to be a list.
     *
     * Positions are counted by each caller rather than taken from the list's
     * keys, because a list that arrived as an object has keys that are not
     * positions, and a refusal naming entry `sonarr` names nothing a reader
     * can count to.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, WireField $list): array
    {
        if (! array_key_exists($list->value, $data)) {
            throw StoredIsUnreadable::missing($list);
        }

        $rows = $data[$list->value];

        if (! is_array($rows)) {
            throw StoredIsUnreadable::missing($list);
        }

        return $rows;
    }

    /**
     * A yes-or-no field of one row.
     *
     * @param array<mixed> $row
     */
    private static function flag(array $row, WireField $list, WireField $field, int $position): bool
    {
        if (! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw StoredIsUnreadable::said($list, $field, $position);
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
            throw StoredIsUnreadable::said($list, $field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw StoredIsUnreadable::said($list, $field, $position);
        }

        return $said;
    }
}
