<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Putting a run back arrived with a word blank.
 *
 * Refused rather than drawn, because each word is one an operator acts on: a
 * run with no stamp is one nobody can ask for, and a change left standing with
 * no reason is the half-undone machine this report exists to explain.
 */
final class UndoSaysNothing extends InvalidArgumentException
{
    /** The field is named, because a report can be long and a refusal naming none is no help. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'Putting a run back arrived with its `%s` blank, and a report that will not say what it is about reads as complete to somebody deciding what to do next.',
            $field,
        ));
    }

    /** A stamp that is not whole seconds since the epoch, quoted as it was handed. */
    public static function notAStamp(string $said): self
    {
        return new self(sprintf(
            'A run to put back was named `%s`, and a run is named by the whole seconds since the epoch it was stamped at.',
            $said,
        ));
    }
}
