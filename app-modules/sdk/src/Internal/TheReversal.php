<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\UndoIsUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * What putting something back came to, read from the rollback's own report.
 *
 * One reading for the two places the report arrives: the `undo` envelope,
 * which {@see \Modules\Sdk\Api\TheRunPutBack} opens, and a plugin install
 * that did not hold, which carries what putting it back came to in the same
 * shape. Written the way {@see \Modules\Sdk\Api\Records} is: a static fold
 * with no state, refusing rather than salvaging.
 *
 * **Whether it was a rehearsal is the stack's `rehearsed`**, read rather than
 * remembered from what was asked.
 *
 * **`noted` may be absent**, which the contract says is the same as empty:
 * nothing about going back needed saying. `reversed` and `left` may not, and a
 * report without `left` is refused rather than read as one that left nothing —
 * that reading is the complete reversal an operator would believe.
 *
 * **Of each reversal, the target and what it does are read.** The rest of an
 * action — which setting, which path, what it held — is the instruction the
 * stack carried out with, and `WhatThisAppDoesNotRead` says why each
 * part is left.
 */
final readonly class TheReversal
{
    /**
     * What putting something back came to.
     *
     * @param array<mixed> $data
     */
    public static function from(array $data): ARunPutBack
    {
        return ARunPutBack::reported(
            self::rehearsed($data),
            WhatWentBack::these(...self::reversed($data)),
            ChangesAndWhy::these(...self::said($data, WireField::Left)),
            ChangesAndWhy::these(...self::noted($data)),
        );
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

        foreach (self::rows($data, WireField::Reversed) as $row) {
            if (! is_array($row)) {
                throw UndoIsUnreadable::entry(WireField::Reversed, WireField::Target, $position);
            }

            $found[] = AChangePutBack::against(
                self::text($row, WireField::Reversed, WireField::Target, $position),
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
            throw UndoIsUnreadable::entry(WireField::Reversed, WireField::Action, $position);
        }

        $said = self::text($row[WireField::Action->value], WireField::Reversed, WireField::Does, $position);

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
        if (! array_key_exists(WireField::Noted->value, $data)) {
            return [];
        }

        return self::said($data, WireField::Noted);
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
     * Returned with their keys, for {@see \Modules\Sdk\Api\Records::rows()}'s reason.
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
