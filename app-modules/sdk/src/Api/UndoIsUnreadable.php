<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\WhatGoingBackDoes;

use function sprintf;

/**
 * The `undo` envelope did not hold what the contract says it holds.
 *
 * {@see HistoryIsUnreadable}'s refusal, for what putting a run back came to.
 * Refused rather than salvaged, and the direction of error is the argument: a
 * row of `left` dropped for being unreadable is a change the screen says went
 * back that did not.
 */
final class UndoIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The undo envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One row of a list was not readable, named by the list, the field and the row. */
    public static function entry(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Row %d of `%s` in the undo envelope has no readable `%s`. It is refused rather than dropped: a report one row short says something went back that did not.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /** A reversal said it does something this app has no word for. */
    public static function does(string $said, int $position): self
    {
        // The accepted list comes from the enum, so a case added cannot leave
        // this message describing the old vocabulary.
        return new self(sprintf(
            'Row %d of `reversed` in the undo envelope says it does `%s`, and this app reads %s.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(WhatGoingBackDoes $does): string => sprintf('`%s`', $does->value),
                WhatGoingBackDoes::cases(),
            )),
        ));
    }
}
