<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ResetEnvelope;
use Modules\Kernel\Api\AnEditReverted;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\EditsReverted;
use Modules\Kernel\Api\TheReset;
use Modules\Sdk\Api\Fields\ResetField;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `reset` envelope into what putting the configuration back reverts.
 *
 * Whether it was carried out is the stack's `confirmed`, read rather than
 * remembered from what was asked, for {@see WhatAnUpgradeComesTo}'s reason: a
 * screen told *carried out* on the strength of having asked would say so of a
 * reset the stack only previewed.
 *
 * Every file is read with its path and its diff, and every connection by its
 * name. A diff may be empty, which is how the stack says the two files differ
 * in no line it can show; one that is absent or not text is refused, as is a
 * list that is not a list.
 */
final readonly class WhatAResetReverts
{
    /**
     * The reset, previewed or carried out.
     *
     * @param Envelope<mixed> $envelope the `reset` envelope a finished job answered with
     */
    public static function in(Envelope $envelope): TheReset
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ResetIsUnreadable::missing(WireField::Data);
        }

        $edits = EditsReverted::these(...self::edits($data));
        $connections = ConnectionsReverted::these(...self::connections($data));

        return self::confirmed($data)
            ? TheReset::carriedOut($edits, $connections)
            : TheReset::previewed($edits, $connections);
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
        return ResetEnvelope::in($envelope)->data;
    }

    /**
     * Whether the operator said yes.
     *
     * @param array<array-key, mixed> $data
     */
    private static function confirmed(array $data): bool
    {
        if (! array_key_exists(WireField::Confirmed->value, $data) || ! is_bool($data[WireField::Confirmed->value])) {
            throw ResetIsUnreadable::missing(WireField::Confirmed);
        }

        return $data[WireField::Confirmed->value];
    }

    /**
     * Every file whose edits go, in the stack's order.
     *
     * @param  array<array-key, mixed> $data
     * @return list<AnEditReverted>
     */
    private static function edits(array $data): array
    {
        $found = [];

        foreach (self::listed($data, ResetField::Reverted) as $position => $edit) {
            if (! is_array($edit)) {
                throw ResetIsUnreadable::entry(ResetField::Reverted, $position);
            }

            $found[] = AnEditReverted::at(
                self::text($edit, WireField::Path, $position),
                self::text($edit, ResetField::Diff, $position),
            );
        }

        return $found;
    }

    /**
     * Every connection that goes with them, by the name the stack gives it.
     *
     * @param  array<array-key, mixed> $data
     * @return list<string>
     */
    private static function connections(array $data): array
    {
        $names = [];

        foreach (self::listed($data, ResetField::RevertedConnections) as $position => $name) {
            if (! is_string($name)) {
                throw ResetIsUnreadable::entry(ResetField::RevertedConnections, $position);
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * A list the answer must carry.
     *
     * @param  array<array-key, mixed> $data
     * @return list<mixed>
     */
    private static function listed(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw ResetIsUnreadable::missing($field);
        }

        $listed = $data[$field->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw ResetIsUnreadable::missing($field);
        }

        return $listed;
    }

    /**
     * A field one file must carry, as text, which may be empty.
     *
     * @param array<array-key, mixed> $edit
     */
    private static function text(array $edit, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $edit) || ! is_string($edit[$field->value])) {
            throw ResetIsUnreadable::entry(ResetField::Reverted, $position);
        }

        return $edit[$field->value];
    }
}
