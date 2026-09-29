<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UndoEnvelope;
use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\UndoField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `undo` envelope, as what putting a run back came to.
 *
 * Written the way {@see Records} is: a static fold with no state, reading
 * through {@see WireField}, refusing rather than salvaging.
 *
 * **Whether it was a rehearsal is the stack's `rehearsed`**, read rather than
 * remembered from what was asked, for {@see WhatAnUpgradeComesTo}'s reason.
 *
 * **`noted` may be absent**, which the contract says is the same as empty:
 * nothing about going back needed saying. `reversed` and `left` may not, and a
 * report without `left` is refused rather than read as one that left nothing —
 * that reading is the complete reversal an operator would believe.
 *
 * **Of each reversal, the target and what it does are read.** The rest of an
 * action — which setting, which path, what it held — is the instruction the
 * stack carried out with, and `WhatTheContractCarriesThatNothingReadsTest`
 * says why each part is left.
 */
final readonly class TheRunPutBack
{
    /**
     * What putting the run back came to.
     *
     * @param Envelope<mixed> $envelope the `undo` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ARunPutBack
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw UndoIsUnreadable::missing(WireField::Data);
        }

        return ARunPutBack::reported(
            self::rehearsed($data),
            WhatWentBack::these(...self::reversed($data)),
            ChangesAndWhy::these(...self::said($data, WireField::Left)),
            ChangesAndWhy::these(...self::noted($data)),
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
        return UndoEnvelope::in($envelope)->data;
    }

    /**
     * Whether this only said what would go back.
     *
     * @param array<mixed> $data
     */
    private static function rehearsed(array $data): WhetherItWasRehearsed
    {
        if (! array_key_exists(WireField::Rehearsed->value, $data) || ! is_bool($data[WireField::Rehearsed->value])) {
            throw UndoIsUnreadable::missing(WireField::Rehearsed);
        }

        return WhetherItWasRehearsed::said($data[WireField::Rehearsed->value]);
    }

    /**
     * Every change that went back, or would, in the stack's order.
     *
     * @param  array<mixed>         $data
     * @return list<AChangePutBack>
     */
    private static function reversed(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, UndoField::Reversed) as $row) {
            if (! is_array($row)) {
                throw UndoIsUnreadable::entry(UndoField::Reversed, WireField::Target, $position);
            }

            $found[] = AChangePutBack::against(
                self::text($row, UndoField::Reversed, WireField::Target, $position),
                self::does($row, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What one reversal does, as a word this app reads.
     *
     * @param array<mixed> $row
     */
    private static function does(array $row, int $position): WhatGoingBackDoes
    {
        if (! array_key_exists(WireField::Action->value, $row) || ! is_array($row[WireField::Action->value])) {
            throw UndoIsUnreadable::entry(UndoField::Reversed, WireField::Action, $position);
        }

        $said = self::text($row[WireField::Action->value], UndoField::Reversed, WireField::Does, $position);

        return WhatGoingBackDoes::tryFrom($said) ?? throw UndoIsUnreadable::does($said, $position);
    }

    /**
     * What going back means beyond the changes, and nothing where the stack left it out.
     *
     * @param  array<mixed>        $data
     * @return list<AChangeAndWhy>
     */
    private static function noted(array $data): array
    {
        if (! array_key_exists(UndoField::Noted->value, $data)) {
            return [];
        }

        return self::said($data, UndoField::Noted);
    }

    /**
     * One list of changes, each with what is said of it, in the stack's order.
     *
     * @param  array<mixed>        $data
     * @return list<AChangeAndWhy>
     */
    private static function said(array $data, NamesAWireField $list): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, $list) as $row) {
            if (! is_array($row)) {
                throw UndoIsUnreadable::entry($list, WireField::Target, $position);
            }

            $found[] = AChangeAndWhy::said(
                self::text($row, $list, WireField::Target, $position),
                self::text($row, $list, WireField::Because, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * The rows of one list, as they arrived.
     *
     * Returned with their keys, for {@see Records::rows()}'s reason.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw UndoIsUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw UndoIsUnreadable::entry($list, $field, $position);
        }

        return $row[$field->value];
    }
}
