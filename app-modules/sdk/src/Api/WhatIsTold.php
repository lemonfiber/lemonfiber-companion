<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AlertsEnvelope;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\WhetherItIsHeard;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `alerts` envelope, as what the operator will be told about.
 *
 * The sibling of {@see WhatLeaves}, written the same way: a static fold with
 * no state, reading through {@see WireField}, and refusing rather than
 * salvaging, by position, anything the kernel would refuse.
 *
 * **`changed` and `rehearsed` are not read.** Both describe what the call that
 * answered did, and this app makes no call that changes the setting.
 */
final readonly class WhatIsTold
{
    /**
     * The preset in force, what it means, and every event set apart from it.
     *
     * @param Envelope<mixed> $envelope the `alerts` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheOperatorIsTold
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw AlertsAreUnreadable::missing(WireField::Data);
        }

        return WhatTheOperatorIsTold::byPreset(
            self::word($data, WireField::Preset),
            self::word($data, WireField::Means),
            SetApart::of(...self::exceptions($data)),
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
        return AlertsEnvelope::in($envelope)->data;
    }

    /**
     * Every event set apart, refusing any this app cannot show.
     *
     * @param  array<mixed>          $data
     * @return list<AnEventSetApart>
     */
    private static function exceptions(array $data): array
    {
        if (! array_key_exists(WireField::Exceptions->value, $data) || ! is_array($data[WireField::Exceptions->value])) {
            throw AlertsAreUnreadable::missing(WireField::Exceptions);
        }

        $found = [];
        $kinds = [];
        $position = 0;

        foreach ($data[WireField::Exceptions->value] as $row) {
            if (! is_array($row)) {
                throw AlertsAreUnreadable::exception($position);
            }

            $kind = self::kind($row, $position);

            if (in_array($kind, $kinds, strict: true)) {
                throw AlertsAreUnreadable::twice($kind, $position);
            }

            $kinds[] = $kind;
            $found[] = AnEventSetApart::of($kind, WhetherItIsHeard::said(wanted: self::wanted($row, $position)));
            $position++;
        }

        return $found;
    }

    /**
     * A top-level word, required and never blank.
     *
     * @param array<mixed> $data
     */
    private static function word(array $data, WireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw AlertsAreUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw AlertsAreUnreadable::missing($field);
        }

        return $said;
    }

    /**
     * An exception's kind, required and never blank.
     *
     * @param array<mixed> $row
     */
    private static function kind(array $row, int $position): string
    {
        if (! array_key_exists(WireField::Kind->value, $row)) {
            throw AlertsAreUnreadable::said(WireField::Kind, $position);
        }

        $said = $row[WireField::Kind->value];

        if (! is_string($said) || trim($said) === '') {
            throw AlertsAreUnreadable::said(WireField::Kind, $position);
        }

        return $said;
    }

    /**
     * Whether an exception is heard about, as the wire's yes or no.
     *
     * @param array<mixed> $row
     */
    private static function wanted(array $row, int $position): bool
    {
        if (! array_key_exists(WireField::Wanted->value, $row) || ! is_bool($row[WireField::Wanted->value])) {
            throw AlertsAreUnreadable::said(WireField::Wanted, $position);
        }

        return $row[WireField::Wanted->value];
    }
}
